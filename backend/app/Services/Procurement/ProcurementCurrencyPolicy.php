<?php

namespace App\Services\Procurement;

use App\Exceptions\InventoryDomainException;
use App\Models\Organization;

class ProcurementCurrencyPolicy
{
    public function resolve(
        Organization $organization,
        ?string $requested,
    ): string {
        $currency = strtoupper($requested ?? $organization->currency_code);

        if ($currency !== strtoupper($organization->currency_code)) {
            throw new InventoryDomainException(
                'procurement_currency_not_supported',
                'Foreign-currency procurement requires the future FX/accounting layer.',
                422,
            );
        }

        return $currency;
    }
}
