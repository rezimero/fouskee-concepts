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

        if (in_array($original->source_reference_type, [
            'stock_count',
            'stock_transfer',
            'stock_transfer_cancel',
        ], true)) {
            throw new InventoryDomainException(
                'inventory_document_movement_reversal_forbidden',
                'This movement is owned by an operational document workflow.',
                409,
            );
        }

        $retry = InventoryMovement::query()
            ->where('organization_id', $organization->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($retry !== null) {
            if (
                $retry->movement_type === InventoryMovementType::Reversal
                && $retry->reversal_of_movement_id === $original->id
                && $retry->business_reason === $reason
            ) {
                return $retry->load('lines');
            }

            throw new InventoryDomainException(
                'inventory_idempotency_conflict',
                'The idempotency key was already used for another command.',
                409,
            );
        }

        if (InventoryMovement::query()
            ->where('reversal_of_movement_id', $original->id)
            ->exists()) {
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
                ? '0.000000'
                : $this->math->negate($line->inventory_value_delta),
        ])->all();

        try {
            return $this->poster->post(
                $organization,
                $actor,
                InventoryMovementType::Reversal,
                $idempotencyKey,
                $lines,
                new InventoryPostingContext(
                    reason: $reason,
                    reversalOf: $original,
                ),
            );
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
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
