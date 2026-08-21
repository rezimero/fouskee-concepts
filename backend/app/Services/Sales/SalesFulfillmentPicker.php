<?php

namespace App\Services\Sales;

use App\Enums\InventoryReservationStatus;
use App\Enums\SalesFulfillmentStatus;
use App\Enums\SalesOrderStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\InventoryReservation;
use App\Models\Organization;
use App\Models\SalesFulfillment;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

class SalesFulfillmentPicker
{
    public function __construct(
        private readonly SalesFulfillmentGuard $guard,
        private readonly SalesFulfillmentIdempotencyGuard $idempotency,
        private readonly SalesFulfillmentReferenceNumber $references,
        private readonly AuditRecorder $audit,
    ) {}

    public function pick(
        Organization $organization,
        User $actor,
        string $orderPublicId,
        string $key,
        array $reservationIds,
    ): SalesFulfillment {
        $fingerprint = $this->idempotency->fingerprint(
            $orderPublicId,
            $reservationIds,
        );

        return DB::transaction(function () use (
            $organization,$actor,$orderPublicId,$key,$reservationIds,$fingerprint,
        ) {
            $retry = $this->idempotency->existingPick(
                $organization,$key,$fingerprint,
            );

            if ($retry !== null) {
                return $retry->load($this->relations());
            }

            $order = SalesOrder::query()
                ->where('organization_id', $organization->id)
                ->where('public_id', $orderPublicId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== SalesOrderStatus::Approved) {
                throw new InventoryDomainException(
                    'sales_order_not_approved_for_pick',
                    'Only an approved sales order can be picked.',
                    409,
                );
            }

            $this->guard->assertNoOpenFulfillment($order);

            $reservations = InventoryReservation::query()
                ->where('organization_id', $organization->id)
                ->where('source_reference_type', SalesReservationSource::ORDER)
                ->where('source_reference_public_id', $order->public_id)
                ->where('status', InventoryReservationStatus::Active)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $this->assertExactSet($reservations, $reservationIds);
            $locationIds = $reservations->pluck('inventory_location_id')->unique();

            if ($locationIds->count() !== 1) {
                throw new InventoryDomainException(
                    'sales_fulfillment_location_mismatch',
                    'One fulfillment must use one inventory location.',
                    409,
                );
            }

            $fulfillment = SalesFulfillment::query()->create([
                'organization_id' => $organization->id,
                'sales_order_id' => $order->id,
                'inventory_location_id' => $locationIds->first(),
                'fulfillment_number' => $this->references->next($organization),
                'status' => SalesFulfillmentStatus::Draft,
                'pick_idempotency_key' => $key,
                'pick_idempotency_fingerprint' => $fingerprint,
                'picked_by_user_id' => $actor->id,
                'picked_at' => now(),
            ]);

            foreach ($reservations as $reservation) {
                $fulfillment->lines()->create([
                    'inventory_reservation_id' => $reservation->id,
                    'product_id' => $reservation->product_id,
                    'inventory_lot_id' => $reservation->inventory_lot_id,
                    'quantity_base_snapshot' => $reservation->quantity_base,
                ]);
            }

            $fulfillment->update(['status' => SalesFulfillmentStatus::Picked]);

            $this->audit->record(
                event: 'sales.fulfillment.picked',
                actor: $actor,
                subject: $fulfillment,
                organizationId: $organization->id,
                after: ['reservation_count' => $reservations->count()],
            );

            return $fulfillment->load($this->relations());
        }, 3);
    }

    private function assertExactSet($reservations, array $ids): void
    {
        $expected = $reservations->pluck('public_id')->sort()->values()->all();
        $actual = collect($ids)->unique()->sort()->values()->all();

        if ($expected === [] || $expected !== $actual) {
            throw new InventoryDomainException(
                'sales_fulfillment_pick_set_mismatch',
                'The picked reservations must exactly match the active order reservation.',
                409,
            );
        }
    }

    private function relations(): array
    {
        return ['order', 'location', 'lines.product', 'lines.lot', 'lines.reservation'];
    }
}
