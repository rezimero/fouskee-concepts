<?php

namespace App\Services\Inventory;

use App\Enums\StockTransferStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

class StockTransferCancellationService
{
    public function __construct(
        private readonly InventoryTransferPostingService $posting,
        private readonly AuditRecorder $audit,
    ) {}

    public function cancel(
        Organization $organization,
        User $actor,
        string $publicId,
        string $key,
        string $reason,
    ): StockTransfer {
        return DB::transaction(function () use (
            $organization, $actor, $publicId, $key, $reason,
        ) {
            $transfer = StockTransfer::query()
                ->with([
                    'lines.product', 'lines.packaging', 'lines.lot',
                    'sourceLocation', 'destinationLocation',
                ])
                ->where('organization_id', $organization->id)
                ->where('public_id', $publicId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transfer->status === StockTransferStatus::Cancelled
                && $transfer->cancel_idempotency_key === $key) {
                return $transfer;
            }

            if ($transfer->status === StockTransferStatus::Received) {
                throw new InventoryDomainException(
                    'inventory_transfer_received_cannot_cancel',
                    'A received transfer must be corrected through reversal.',
                    409,
                );
            }

            $movement = null;

            if ($transfer->status === StockTransferStatus::InTransit) {
                $transit = $transfer->transitLocation()->firstOrFail();

                $movement = $this->posting->post(
                    $organization,
                    $actor,
                    $key,
                    $transit,
                    $transfer->sourceLocation,
                    $this->resolvedLines($transfer),
                    new InventoryPostingContext(
                        reason: $reason,
                        sourceReferenceType: 'stock_transfer_cancel',
                        sourceReferencePublicId: $transfer->public_id,
                    ),
                );
            }

            $transfer->update([
                'status' => StockTransferStatus::Cancelled,
                'cancelled_by_user_id' => $actor->id,
                'cancel_movement_id' => $movement?->id,
                'cancel_idempotency_key' => $key,
                'cancelled_at' => now(),
            ]);

            $this->audit->record(
                event: 'inventory.transfer.cancelled',
                actor: $actor,
                subject: $transfer,
                organizationId: $organization->id,
                after: ['status' => 'cancelled'],
                reason: $reason,
            );

            return $transfer;
        }, 3);
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
}
