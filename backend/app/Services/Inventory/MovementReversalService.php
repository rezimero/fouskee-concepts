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
    private const OWNED_SOURCE_TYPES = [
        'stock_count',
        'stock_transfer',
        'stock_transfer_cancel',
        'procurement.goods_receipt',
    ];

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
        return $this->perform(
            $organization,
            $actor,
            $movementPublicId,
            $idempotencyKey,
            $reason,
            false,
        );
    }

    public function reverseOwned(
        Organization $organization,
        User $actor,
        string $movementPublicId,
        string $idempotencyKey,
        string $reason,
    ) {
        return $this->perform(
            $organization,
            $actor,
            $movementPublicId,
            $idempotencyKey,
            $reason,
            true,
        );
    }

    private function perform(
        Organization $organization,
        User $actor,
        string $movementPublicId,
        string $idempotencyKey,
        string $reason,
        bool $allowOwned,
    ) {
        $original = InventoryMovement::query()
            ->with('lines')
            ->where('organization_id', $organization->id)
            ->where('public_id', $movementPublicId)
            ->firstOrFail();

        $this->assertReversible($original, $allowOwned);

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

            throw $this->idempotencyConflict();
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

        try {
            return $this->poster->post(
                $organization,
                $actor,
                InventoryMovementType::Reversal,
                $idempotencyKey,
                $this->reversalLines($original),
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

    private function assertReversible(
        InventoryMovement $movement,
        bool $allowOwned,
    ): void {
        if ($movement->movement_type === InventoryMovementType::Reversal) {
            throw new InventoryDomainException(
                'inventory_reversal_of_reversal_not_permitted',
                'A reversal movement cannot itself be reversed.',
                409,
            );
        }

        if (
            ! $allowOwned
            && in_array($movement->source_reference_type, self::OWNED_SOURCE_TYPES, true)
        ) {
            throw new InventoryDomainException(
                'inventory_document_movement_reversal_forbidden',
                'This movement is owned by an operational document workflow.',
                409,
            );
        }
    }

    private function reversalLines(InventoryMovement $movement): array
    {
        return $movement->lines->map(fn ($line) => [
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
    }

    private function idempotencyConflict(): InventoryDomainException
    {
        return new InventoryDomainException(
            'inventory_idempotency_conflict',
            'The idempotency key was already used for another command.',
            409,
        );
    }
}
