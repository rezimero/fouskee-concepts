<?php

namespace App\Services\Sales;

use App\Exceptions\InventoryDomainException;
use App\Models\InventoryLocation;
use App\Services\Support\DecimalMath;
use Illuminate\Support\Collection;

class SalesOrderReservationIntegrity
{
    public function __construct(private readonly DecimalMath $math) {}

    public function assertMatches(
        Collection $reservations,
        InventoryLocation $location,
        array $requirements,
    ): void {
        if ($reservations->contains(
            fn ($item) => $item->inventory_location_id !== $location->id,
        )) {
            throw new InventoryDomainException(
                'sales_order_already_reserved_elsewhere',
                'The order already has stock reserved at another location.',
                409,
            );
        }

        $actual = [];

        foreach ($reservations as $reservation) {
            $productId = $reservation->product_id;
            $actual[$productId] = $this->math->add(
                $actual[$productId] ?? '0.000000',
                (string) $reservation->quantity_base,
            );
        }

        foreach ($requirements as $productId => $requirement) {
            if (($actual[$productId] ?? null) !== $requirement['quantity_base']) {
                throw $this->mismatch();
            }
            unset($actual[$productId]);
        }

        if ($actual !== []) {
            throw $this->mismatch();
        }
    }

    private function mismatch(): InventoryDomainException
    {
        return new InventoryDomainException(
            'sales_order_reservation_integrity_mismatch',
            'Existing reservations do not match the approved sales order.',
            409,
        );
    }
}
