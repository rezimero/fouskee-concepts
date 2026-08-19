<?php

namespace App\Services\Sales;

use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\SalesOrder;

class SalesIdempotencyGuard
{
    public function fingerprint(array $command): string
    {
        return hash('sha256', json_encode($command, JSON_THROW_ON_ERROR));
    }

    public function existing(
        Organization $organization,
        string $key,
        string $fingerprint,
    ): ?SalesOrder {
        $order = SalesOrder::query()
            ->where('organization_id', $organization->id)
            ->where('idempotency_key', $key)
            ->first();

        if ($order === null) {
            return null;
        }

        if ($order->idempotency_fingerprint !== $fingerprint) {
            throw new InventoryDomainException(
                'sales_idempotency_conflict',
                'The idempotency key was already used for another sales order.',
                409,
            );
        }

        return $order;
    }
}
