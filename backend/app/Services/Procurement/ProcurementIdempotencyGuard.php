<?php

namespace App\Services\Procurement;

use App\Exceptions\InventoryDomainException;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;

class ProcurementIdempotencyGuard
{
    public function existing(
        string $modelClass,
        Organization $organization,
        string $key,
        string $fingerprint,
    ): ?Model {
        $document = $modelClass::query()
            ->where('organization_id', $organization->id)
            ->where('idempotency_key', $key)
            ->first();

        if ($document === null) {
            return null;
        }

        if ($document->idempotency_fingerprint !== $fingerprint) {
            throw new InventoryDomainException(
                'procurement_idempotency_conflict',
                'The idempotency key was already used for another command.',
                409,
            );
        }

        return $document;
    }

    public function fingerprint(array $command): string
    {
        return hash(
            'sha256',
            json_encode($command, JSON_THROW_ON_ERROR),
        );
    }
}
