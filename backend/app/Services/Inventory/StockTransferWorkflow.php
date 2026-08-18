<?php

namespace App\Services\Inventory;

use App\Enums\StockTransferStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

class StockTransferWorkflow
{
    private const RELATIONS = [
        'lines.product', 'lines.packaging', 'lines.lot',
        'sourceLocation', 'destinationLocation',
    ];

    public function __construct(
        private readonly InventoryTransitLocationService $transit,
        private readonly InventoryTransferPostingService $posting,
        private readonly InventoryPolicyService $policies,
        private readonly StockTransferPostingContextFactory $contexts,
        private readonly AuditRecorder $audit,
    ) {}

    public function dispatch(
        Organization $organization,
        User $actor,
        string $publicId,
        string $key,
        ?string $occurredAt,
        ?string $reason,
    ): StockTransfer {
        return DB::transaction(function () use (
            $organization, $actor, $publicId, $key, $occurredAt, $reason,
        ) {
            $transfer = $this->locked($organization, $publicId);

            if ($transfer->status === StockTransferStatus::InTransit
                && $transfer->dispatch_idempotency_key === $key) {
                return $transfer;
            }

            $this->assertStatus($transfer, StockTransferStatus::Draft);
            $transit = $this->transit->for($organization, $actor);
            $movement = $this->posting->post(
                $organization,
                $actor,
                $key,
                $transfer->sourceLocation,
                $transit,
                $this->resolvedLines($transfer),
                $this->contexts->make($organization, $actor, $transfer, $occurredAt, $reason),
            );

            $transfer->update([
                'transit_location_id' => $transit->id,
                'status' => StockTransferStatus::InTransit,
                'dispatched_by_user_id' => $actor->id,
                'dispatch_movement_id' => $movement->id,
                'dispatch_idempotency_key' => $key,
                'dispatched_at' => now(),
            ]);

            $this->record($transfer, $actor, 'inventory.transfer.dispatched', $reason);

            return $transfer->load(self::RELATIONS);
        }, 3);
    }

    public function receive(
        Organization $organization,
        User $actor,
        string $publicId,
        string $key,
        ?string $occurredAt,
        ?string $reason,
    ): StockTransfer {
        return DB::transaction(function () use (
            $organization, $actor, $publicId, $key, $occurredAt, $reason,
        ) {
            $transfer = $this->locked($organization, $publicId);

            if ($transfer->status === StockTransferStatus::Received
                && $transfer->receive_idempotency_key === $key) {
                return $transfer;
            }

            $this->assertStatus($transfer, StockTransferStatus::InTransit);

            if (
                $this->policies->for($organization)->require_separate_transfer_receiver
                && $transfer->dispatched_by_user_id === $actor->id
            ) {
                throw new InventoryDomainException(
                    'inventory_transfer_self_receive_forbidden',
                    'A separate user must confirm receipt of this transfer.',
                    409,
                );
            }

            $transit = $transfer->transitLocation()->firstOrFail();
            $movement = $this->posting->post(
                $organization,
                $actor,
                $key,
                $transit,
                $transfer->destinationLocation,
                $this->resolvedLines($transfer),
                $this->contexts->make($organization, $actor, $transfer, $occurredAt, $reason),
            );

            $transfer->update([
                'status' => StockTransferStatus::Received,
                'received_by_user_id' => $actor->id,
                'receive_movement_id' => $movement->id,
                'receive_idempotency_key' => $key,
                'received_at' => now(),
            ]);

            $this->record($transfer, $actor, 'inventory.transfer.received', $reason);

            return $transfer->load(self::RELATIONS);
        }, 3);
    }

    private function locked(
        Organization $organization,
        string $publicId,
    ): StockTransfer {
        return StockTransfer::query()
            ->with(self::RELATIONS)
            ->where('organization_id', $organization->id)
            ->where('public_id', $publicId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function resolvedLines(StockTransfer $transfer): array
    {
        return $transfer->lines->map(fn ($line) => [
            'product_id' => $line->product_id,
            'product_packaging_id' => $line->product_packaging_id,
            'inventory_lot_id' => $line->inventory_lot_id,
            'entered_quantity' => $line->entered_quantity,
            'conversion_to_base_snapshot' => $line->conversion_to_base_snapshot,
            'quantity_base' => $line->quantity_base,
        ])->all();
    }

    private function assertStatus(
        StockTransfer $transfer,
        StockTransferStatus $status,
    ): void {
        if ($transfer->status !== $status) {
            throw new InventoryDomainException(
                'inventory_transfer_invalid_status',
                "Transfer must be {$status->value} for this action.",
                409,
            );
        }
    }

    private function record(
        StockTransfer $transfer,
        User $actor,
        string $event,
        ?string $reason,
    ): void {
        $this->audit->record(
            event: $event,
            actor: $actor,
            subject: $transfer,
            organizationId: $transfer->organization_id,
            after: ['status' => $transfer->status->value],
            reason: $reason,
        );
    }
}
