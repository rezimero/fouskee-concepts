<?php

namespace App\Services\Inventory;

use App\Enums\InventoryMovementType;
use App\Exceptions\InventoryDomainException;
use App\Models\InventoryMovement;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\QueryException;

class MovementReversalService
{
    public function __construct(
        private readonly InventoryPoster $poster,
        private readonly InventoryQuantityMath $math,
    ) {}

    public function reverse(
        Organization $organization,
        User $actor,
        string $movementPublicId,
        string $idempotencyKey,
        string $reason,
    ) {
        $original = InventoryMovement::query()
            ->with('lines')
            ->where('organization_id', $organization->id)
            ->where('public_id', $movementPublicId)
            ->firstOrFail();

        if ($original->movement_type === InventoryMovementType::Reversal) {
            throw new InventoryDomainException(
                'inventory_reversal_of_reversal_not_permitted',
                'A reversal movement cannot itself be reversed.',
                409,
            );
        }

        $existingRetry = InventoryMovement::query()
            ->where('organization_id', $organization->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existingRetry !== null) {
            if (
                $existingRetry->movement_type === InventoryMovementType::Reversal
                && $existingRetry->reversal_of_movement_id === $original->id
                && $existingRetry->business_reason === $reason
            ) {
                return $existingRetry->load('lines');
            }

            throw new InventoryDomainException(
                'inventory_idempotency_conflict',
                'The idempotency key was already used for another command.',
                409,
            );
        }

        $alreadyReversed = InventoryMovement::query()
            ->where('organization_id', $organization->id)
            ->where('reversal_of_movement_id', $original->id)
            ->exists();

        if ($alreadyReversed) {
            throw new InventoryDomainException(
                'inventory_movement_already_reversed',
                'The inventory movement has already been reversed.',
                409,
            );
        }

        $lines = $original->lines->map(fn ($line) => [
            'inventory_location_id' => $line->inventory_location_id,
            'product_id' => $line->product_id,
            'product_packaging_id' => $line->product_packaging_id,
            'inventory_lot_id' => $line->inventory_lot_id,
            'entered_quantity' => $this->math->negate($line->entered_quantity),
            'conversion_to_base_snapshot' => $line->conversion_to_base_snapshot,
            'quantity_base' => $this->math->negate($line->quantity_base),
            'unit_cost_snapshot' => $line->unit_cost_snapshot,
            'inventory_value_delta' => $line->inventory_value_delta === null
                ? null
                : $this->math->negate($line->inventory_value_delta),
        ])->all();

        try {
            return $this->poster->post(
                $organization,
                $actor,
                InventoryMovementType::Reversal,
                $idempotencyKey,
                $lines,
                $reason,
                $original,
            );
        } catch (QueryException $exception) {
            if ((int) $exception->errorInfo[1] === 1062) {
                throw new InventoryDomainException(
                    'inventory_movement_already_reversed',
                    'The inventory movement has already been reversed.',
                    409,
                );
            }

            throw $exception;
        }
    }
}
