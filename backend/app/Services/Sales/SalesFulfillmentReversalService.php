<?php

namespace App\Services\Sales;

use App\Enums\SalesFulfillmentStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\SalesFulfillment;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Inventory\MovementReversalService;
use Illuminate\Support\Facades\DB;

class SalesFulfillmentReversalService
{
    public function __construct(
        private readonly MovementReversalService $reversals,
        private readonly InventoryReservationService $reservations,
        private readonly AuditRecorder $audit,
    ) {}

    public function reverse(
        Organization $organization,
        User $actor,
        string $publicId,
        string $idempotencyKey,
        string $reason,
    ): SalesFulfillment {
        return DB::transaction(function () use (
            $organization,$actor,$publicId,$idempotencyKey,$reason,
        ) {
            $fulfillment = SalesFulfillment::query()
                ->with($this->relations())
                ->where('organization_id', $organization->id)
                ->where('public_id', $publicId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($fulfillment->status === SalesFulfillmentStatus::Reversed) {
                return $this->retry($fulfillment, $idempotencyKey, $reason);
            }

            if ($fulfillment->status !== SalesFulfillmentStatus::Dispatched) {
                throw new InventoryDomainException(
                    'sales_fulfillment_not_dispatched',
                    'Only a dispatched fulfillment can be reversed.',
                    409,
                );
            }

            $movement = $this->reversals->reverseOwned(
                $organization,
                $actor,
                $fulfillment->movement->public_id,
                $idempotencyKey,
                $reason,
            );

            foreach ($fulfillment->lines as $line) {
                $this->reservations->createOwned(
                    $organization,
                    $actor,
                    [
                        'location_id' => $fulfillment->location->public_id,
                        'product_id' => $line->product->public_id,
                        'lot_id' => $line->lot?->public_id,
                        'quantity_base' => $line->quantity_base_snapshot,
                        'source_reference_type' => SalesReservationSource::ORDER,
                        'source_reference_public_id' =>
                            $fulfillment->order->public_id,
                    ],
                );
            }

            $fulfillment->update([
                'status' => SalesFulfillmentStatus::Reversed,
                'reversal_idempotency_key' => $idempotencyKey,
                'reversal_movement_id' => $movement->id,
                'reversed_by_user_id' => $actor->id,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ]);

            $this->audit->record(
                event: 'sales.fulfillment.reversed',
                actor: $actor,
                subject: $fulfillment,
                organizationId: $organization->id,
                after: ['reversal_movement_id' => $movement->public_id],
                reason: $reason,
            );

            return $fulfillment->load($this->relations());
        }, 3);
    }

    private function retry(
        SalesFulfillment $fulfillment,
        string $key,
        string $reason,
    ): SalesFulfillment {
        if (
            $fulfillment->reversal_idempotency_key !== $key
            || $fulfillment->reversal_reason !== $reason
        ) {
            throw new InventoryDomainException(
                'sales_fulfillment_idempotency_conflict',
                'The fulfillment was already reversed with another command.',
                409,
            );
        }

        return $fulfillment->load($this->relations());
    }

    private function relations(): array
    {
        return [
            'order', 'location', 'movement', 'reversalMovement',
            'lines.reservation', 'lines.product', 'lines.lot',
        ];
    }
}
