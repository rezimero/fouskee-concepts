<?php

namespace App\Services\Sales;

use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use App\Models\SalesFulfillment;

class SalesFulfillmentIdempotencyGuard
{
    public function fingerprint(string $orderId, array $reservationIds): string
    {
        sort($reservationIds);

        return hash('sha256', json_encode([
            'order_id' => $orderId,
            'reservation_ids' => array_values($reservationIds),
        ], JSON_THROW_ON_ERROR));
    }

    public function existingPick(
        Organization $organization,
        string $key,
        string $fingerprint,
    ): ?SalesFulfillment {
        $fulfillment = SalesFulfillment::query()
            ->where('organization_id', $organization->id)
            ->where('pick_idempotency_key', $key)
            ->first();

        if ($fulfillment === null) {
            return null;
        }

        if ($fulfillment->pick_idempotency_fingerprint !== $fingerprint) {
            throw new InventoryDomainException(
                'sales_fulfillment_idempotency_conflict',
                'The idempotency key belongs to another fulfillment command.',
                409,
            );
        }

        return $fulfillment;
    }
}
