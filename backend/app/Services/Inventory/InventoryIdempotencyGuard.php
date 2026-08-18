<?php

namespace App\Services\Inventory;

use App\Exceptions\InventoryDomainException;
use App\Models\InventoryMovement;
use App\Models\Organization;

class InventoryIdempotencyGuard
{
    public function existing(
        Organization $organization,
        string $key,
        string $fingerprint,
    ): ?InventoryMovement {
        $movement = InventoryMovement::query()
            ->where('organization_id', $organization->id)
            ->where('idempotency_key', $key)
            ->first();

        if ($movement === null) {
            return null;
        }

        if ($movement->idempotency_fingerprint !== $fingerprint) {
            throw new InventoryDomainException(
                'inventory_idempotency_conflict',
                'The idempotency key was already used for another command.',
                409,
            );
        }

        return $movement->load('lines');
    }
}
