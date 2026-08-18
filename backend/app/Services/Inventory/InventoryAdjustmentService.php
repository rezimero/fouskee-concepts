<?php

namespace App\Services\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\InventoryLocation;
use App\Models\Organization;
use App\Models\User;

class InventoryAdjustmentService
{
    public function __construct(
        private readonly InventoryLineResolver $resolver,
        private readonly InventoryPoster $poster,
        private readonly InventoryQuantityMath $math,
    ) {}

    public function post(
        Organization $organization,
        User $actor,
        string $idempotencyKey,
        string $locationPublicId,
        array $inputLines,
        string $reason,
    ) {
        $location = InventoryLocation::query()
            ->where('organization_id', $organization->id)
            ->where('public_id', $locationPublicId)
            ->firstOrFail();

        $lines = [];
        foreach ($inputLines as $input) {
            $resolved = $this->resolver->resolve($organization, $input, true);
            $unitCost = $resolved['unit_cost_snapshot'];

            $lines[] = [
                'inventory_location_id' => $location->id,
                'product_id' => $resolved['product']->id,
                'product_packaging_id' => $resolved['packaging']?->id,
                'inventory_lot_id' => null,
                'entered_quantity' => $resolved['entered_quantity'],
                'conversion_to_base_snapshot' => $resolved['conversion_to_base_snapshot'],
                'quantity_base' => $resolved['quantity_base'],
                'unit_cost_snapshot' => $unitCost,
                'inventory_value_delta' => $unitCost === null
                    ? null
                    : $this->math->multiply($resolved['quantity_base'], $unitCost),
            ];
        }

        return $this->poster->post(
            $organization,
            $actor,
            InventoryMovementType::Adjustment,
            $idempotencyKey,
            $lines,
            $reason,
        );
    }
}
