<?php

namespace App\Services\Sales;

use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\SalesQuotation;

class SalesQuotationIdempotencyGuard
{
    public function fingerprint(array $command): string
    {
        return hash('sha256', json_encode($command, JSON_THROW_ON_ERROR));
    }

    public function existing(
        Organization $organization,
        string $key,
        string $fingerprint,
    ): ?SalesQuotation {
        $quotation = SalesQuotation::query()
            ->where('organization_id', $organization->id)
            ->where('idempotency_key', $key)
            ->first();

        if ($quotation === null) {
            return null;
        }

        if ($quotation->idempotency_fingerprint !== $fingerprint) {
            throw new InventoryDomainException(
                'sales_quotation_idempotency_conflict',
                'The idempotency key belongs to another quotation.',
                409,
            );
        }

        return $quotation;
    }
}
