<?php

namespace App\Services\Sales;

use App\Enums\SalesOrderStatus;
use App\Exceptions\InventoryDomainException;
use App\Models\InventoryLocation;
use App\Models\InventoryReservation;
use App\Models\Organization;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Inventory\InventoryLocationResolver;
use App\Services\Inventory\InventoryReservationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesOrderReservationService
{
    public function __construct(
        private readonly InventoryLocationResolver $locations,
        private readonly SalesOrderReservationRequirements $requirements,
        private readonly SalesOrderReservationIntegrity $integrity,
        private readonly SalesOrderReservationAllocator $allocator,
        private readonly InventoryReservationService $reservations,
        private readonly SalesOrderReservationSummary $summary,
        private readonly SalesFulfillmentGuard $fulfillmentGuard,
        private readonly AuditRecorder $audit,
    ) {}

    public function reserve(
        Organization $organization,
        User $actor,
        string $orderPublicId,
        string $locationPublicId,
    ): array {
        return DB::transaction(function () use (
            $organization, $actor, $orderPublicId, $locationPublicId,
        ) {
            $order = $this->lockedOrder($organization, $orderPublicId);
            $this->assertApproved($order);
            $location = $this->locations->active($organization, $locationPublicId);
            $this->assertFulfillmentLocation($location);
            $requirements = $this->requirements->for($order);
            $existing = $this->active($order);

            if ($existing->isNotEmpty()) {
                $this->integrity->assertMatches(
                    $existing,
                    $location,
                    $requirements,
                );

                return $this->summary->for($order);
            }

            $created = $this->allocator->allocate(
                $organization,
                $actor,
                $order,
                $location,
                $requirements,
            );

            $this->audit->record(
                event: 'sales.order.inventory_reserved',
                actor: $actor,
                subject: $order,
                organizationId: $organization->id,
                after: [
                    'location_id' => $location->public_id,
                    'reservation_count' => $created->count(),
                ],
            );

            return $this->summary->for($order);
        }, 3);
    }

    public function release(
        Organization $organization,
        User $actor,
        string $orderPublicId,
        string $reason,
    ): array {
        return DB::transaction(function () use (
            $organization, $actor, $orderPublicId, $reason,
        ) {
            $order = $this->lockedOrder($organization, $orderPublicId);
            $this->assertApproved($order);
            $this->fulfillmentGuard->assertReservationReleaseAllowed($order);
            $active = $this->active($order);

            foreach ($active as $reservation) {
                $this->reservations->releaseOwned(
                    $organization,
                    $actor,
                    $reservation->public_id,
                );
            }

            if ($active->isNotEmpty()) {
                $this->audit->record(
                    event: 'sales.order.inventory_reservation_released',
                    actor: $actor,
                    subject: $order,
                    organizationId: $organization->id,
                    after: ['reservation_count' => $active->count()],
                    reason: $reason,
                );
            }

            return $this->summary->for($order);
        }, 3);
    }

    public function summary(
        Organization $organization,
        string $orderPublicId,
    ): array {
        $order = SalesOrder::query()
            ->where('organization_id', $organization->id)
            ->where('public_id', $orderPublicId)
            ->firstOrFail();

        return $this->summary->for($order);
    }

    private function lockedOrder(
        Organization $organization,
        string $publicId,
    ): SalesOrder {
        return SalesOrder::query()
            ->with(['lines.product'])
            ->where('organization_id', $organization->id)
            ->where('public_id', $publicId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function active(SalesOrder $order): Collection
    {
        return InventoryReservation::query()
            ->where('organization_id', $order->organization_id)
            ->where('source_reference_type', SalesReservationSource::ORDER)
            ->where('source_reference_public_id', $order->public_id)
            ->where('status', 'active')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    private function assertApproved(SalesOrder $order): void
    {
        if ($order->status !== SalesOrderStatus::Approved) {
            throw new InventoryDomainException(
                'sales_order_not_approved_for_reservation',
                'Only an approved sales order can reserve inventory.',
                409,
            );
        }
    }

    private function assertFulfillmentLocation(InventoryLocation $location): void
    {
        if ($location->code === InventoryLocation::SYSTEM_TRANSIT_CODE) {
            throw new InventoryDomainException(
                'sales_order_invalid_fulfillment_location',
                'The system transit location cannot fulfill customer orders.',
                409,
            );
        }
    }
}
