<?php

namespace App\Services\Sales;

use App\Exceptions\InventoryDomainException;

final class SalesFulfillmentQuantityValidator
{
    public function validate(float $fulfillmentQuantity, float $reservedQuantity): void
    {
        if ($fulfillmentQuantity <= 0) {
            throw new InventoryDomainException(
                'sales_fulfillment_invalid_quantity',
                'Fulfillment quantity must be greater than zero.',
                422,
            );
        }

        if ($fulfillmentQuantity > $reservedQuantity) {
            throw new InventoryDomainException(
                'sales_fulfillment_over_reserved_quantity',
                'Fulfillment quantity cannot exceed reserved quantity.',
                422,
            );
        }
    }
}
