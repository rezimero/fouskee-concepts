<?php

namespace App\Services\Sales;

use App\Exceptions\InventoryDomainException;
use App\Models\InventoryLocation;
use App\Models\Organization;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\Inventory\InventoryBalanceService;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Support\DecimalMath;
use Illuminate\Support\Collection;

class SalesOrderReservationAllocator
{
    public function __construct(
        private readonly SalesReservationCandidateFinder $candidates,
        private readonly InventoryBalanceService $balances,
        private readonly InventoryReservationService $reservations,
        private readonly DecimalMath $math,
    ) {}

    public function allocate(
        Organization $organization,
        User $actor,
        SalesOrder $order,
        InventoryLocation $location,
        array $requirements,
    ): Collection {
        $created = collect();

        foreach ($requirements as $requirement) {
            $remaining = $requirement['quantity_base'];
            $product = $requirement['product'];

            foreach ($this->candidates->for(
                $organization,
                $location,
                $product,
            ) as $balance) {
                $reserved = $this->balances->reservedBase(
                    $organization->id,
                    $location->id,
                    $product->id,
                    $balance->inventory_lot_id,
                );
                $available = $this->math->subtract(
                    $balance->quantity_on_hand_base,
                    $reserved,
                );

                if (! $this->math->greaterThan($available, '0')) {
                    continue;
                }

                $quantity = $this->math->greaterThan($available, $remaining)
                    ? $remaining
                    : $available;

                $created->push($this->reservations->createOwned(
                    $organization,
                    $actor,
                    [
                        'location_id' => $location->public_id,
                        'product_id' => $product->public_id,
                        'lot_id' => $balance->lot?->public_id,
                        'quantity_base' => $quantity,
                        'source_reference_type' => SalesReservationSource::ORDER,
                        'source_reference_public_id' => $order->public_id,
                    ],
                ));

                $remaining = $this->math->subtract($remaining, $quantity);

                if ($this->math->isZero($remaining)) {
                    break;
                }
            }

            if ($this->math->greaterThan($remaining, '0')) {
                throw new InventoryDomainException(
                    'sales_order_insufficient_fulfillment_stock',
                    'The selected location cannot fully reserve this order.',
                    409,
                );
            }
        }

        return $created;
    }
}
