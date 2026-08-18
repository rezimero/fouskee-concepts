<?php

namespace App\Services\Procurement;

use App\Exceptions\InventoryDomainException;

class SupplierInvoiceNumberNormalizer
{
    public function normalize(string $number): string
    {
        $normalized = preg_replace(
            '/[^A-Z0-9]/',
            '',
            strtoupper(trim($number)),
        );

        if ($normalized === null || $normalized === '') {
            throw new InventoryDomainException(
                'procurement_invoice_number_invalid',
                'Supplier invoice number must contain letters or digits.',
                422,
            );
        }

        return $normalized;
    }
}
