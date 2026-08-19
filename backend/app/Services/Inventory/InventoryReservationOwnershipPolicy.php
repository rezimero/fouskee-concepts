<?php

namespace App\Services\Inventory;

use App\Exceptions\InventoryDomainException;

class InventoryReservationOwnershipPolicy
{
    private const OWNED_SOURCE_TYPES = ['sales.order'];

    public function assertAllowed(?string $sourceType, bool $allowOwned): void
    {
        $owned = in_array($sourceType, self::OWNED_SOURCE_TYPES, true);

        if ($owned && ! $allowOwned) {
            throw new InventoryDomainException(
                'inventory_owned_reservation_operation_forbidden',
                'This reservation belongs to an operational document workflow.',
                409,
            );
        }

        if ($allowOwned && ! $owned) {
            throw new InventoryDomainException(
                'inventory_reservation_not_owned',
                'The reservation is not owned by this workflow.',
                409,
            );
        }
    }
}
