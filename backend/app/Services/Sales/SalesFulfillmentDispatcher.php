<?php

namespace App\Services\Sales;

use App\Enums\InventoryMovementType;
use App\Enums\InventoryReservationStatus;
use App\Enums\SalesFulfillmentStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\InventoryReservation;
use App\Models\Organization;
use App\Models\SalesFulfillment;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Inventory\InventoryOwnedReservationFulfillmentService;
use App\Services\Inventory\InventoryPoster;
use App\Services\Inventory\InventoryPostingContext;
use App\Services\Inventory\InventoryQuantityMath;
use Illuminate\Support\Facades\DB;

class SalesFulfillmentDispatcher
{
    public function __construct(
        private readonly InventoryOwnedReservationFulfillmentService $consumer,
        private readonly InventoryPoster $poster,
        private readonly InventoryQuantityMath $math,
        private readonly AuditRecorder $audit,
    ) {}

    public function dispatch(
        Organization $organization,
        User $actor,
        string $publicId,
        string $idempotencyKey,
    ): SalesFulfillment {
        return DB::transaction(function () use (
            $organization,$actor,$publicId,$idempotencyKey,
        ) {
            $fulfillment = $this->locked($organization, $publicId);

            if ($fulfillment->status === SalesFulfillmentStatus::Dispatched) {
                return $this->retry($fulfillment, $idempotencyKey);
            }

            if ($fulfillment->status !== SalesFulfillmentStatus::Picked) {
                throw new InventoryDomainException(
                    'sales_fulfillment_not_picked',
                    'Only a picked fulfillment can be dispatched.',
                    409,
                );
            }

            $reservations = $this->lockedReservations($fulfillment);
            $this->consumer->consume($organization, $actor, $reservations);

            $movement = $this->poster->post(
                $organization,
                $actor,
                InventoryMovementType::SaleIssue,
                $idempotencyKey,
                $this->movementLines($fulfillment, $reservations),
                new InventoryPostingContext(
                    sourceReferenceType: 'sales.fulfillment',
                    sourceReferencePublicId: $fulfillment->public_id,
                ),
            );

            $fulfillment->update([
                'status' => SalesFulfillmentStatus::Dispatched,
                'dispatch_idempotency_key' => $idempotencyKey,
                'inventory_movement_id' => $movement->id,
                'dispatched_by_user_id' => $actor->id,
                'dispatched_at' => now(),
            ]);

            $this->audit->record(
                event: 'sales.fulfillment.dispatched',
                actor: $actor,
                subject: $fulfillment,
                organizationId: $organization->id,
                after: ['inventory_movement_id' => $movement->public_id],
            );

            return $fulfillment->load($this->relations());
        }, 3);
    }

    private function locked(
        Organization $organization,
        string $publicId,
    ): SalesFulfillment {
        return SalesFulfillment::query()
            ->with($this->relations())
            ->where('organization_id', $organization->id)
            ->where('public_id', $publicId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function lockedReservations(SalesFulfillment $fulfillment)
    {
        $ids = $fulfillment->lines->pluck('inventory_reservation_id');

        $reservations = InventoryReservation::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if (
            $reservations->count() !== $ids->count()
            || $reservations->contains(
                fn ($item) => $item->status !== InventoryReservationStatus::Active,
            )
        ) {
            throw new InventoryDomainException(
                'sales_fulfillment_reservation_changed',
                'Picked reservation evidence changed before dispatch.',
                409,
            );
        }

        return $reservations;
    }

    private function movementLines(
        SalesFulfillment $fulfillment,
        $reservations,
    ): array {
        return $reservations->map(fn ($reservation) => [
            'inventory_location_id' => $fulfillment->inventory_location_id,
            'product_id' => $reservation->product_id,
            'product_packaging_id' => null,
            'inventory_lot_id' => $reservation->inventory_lot_id,
            'entered_quantity' => $this->math->negate($reservation->quantity_base),
            'conversion_to_base_snapshot' => '1.000000',
            'quantity_base' => $this->math->negate($reservation->quantity_base),
            'unit_cost_snapshot' => null,
            'inventory_value_delta' => null,
        ])->all();
    }

    private function retry(
        SalesFulfillment $fulfillment,
        string $key,
    ): SalesFulfillment {
        if ($fulfillment->dispatch_idempotency_key !== $key) {
            throw new InventoryDomainException(
                'sales_fulfillment_idempotency_conflict',
                'The fulfillment was already dispatched with another key.',
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
