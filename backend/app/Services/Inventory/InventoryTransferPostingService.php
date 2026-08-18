<?php

namespace App\Services\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\InventoryLocation;
use App\Models\Organization;
use App\Models\User;

class InventoryTransferPostingService
{
    public function __construct(
        private readonly InventoryPoster $poster,
        private readonly InventoryQuantityMath $math,
    ) {}

    public function post(
        Organization $organization,
        User $actor,
        string $idempotencyKey,
        InventoryLocation $source,
        InventoryLocation $destination,
        array $resolvedLines,
        ?InventoryPostingContext $context = null,
    ) {
        $lines = [];

        foreach ($resolvedLines as $resolved) {
            $base = (string) $resolved['quantity_base'];
            $common = [
                'product_id' => $resolved['product_id'],
                'product_packaging_id' => $resolved['product_packaging_id'] ?? null,
                'inventory_lot_id' => $resolved['inventory_lot_id'] ?? null,
                'entered_quantity' => $resolved['entered_quantity'],
                'conversion_to_base_snapshot' => $resolved['conversion_to_base_snapshot'],
                'unit_cost_snapshot' => null,
                'inventory_value_delta' => null,
            ];

            $lines[] = $common + [
                'inventory_location_id' => $source->id,
                'quantity_base' => $this->math->negate($base),
            ];

            $lines[] = $common + [
                'inventory_location_id' => $destination->id,
                'quantity_base' => $base,
            ];
        }

        return $this->poster->post(
            $organization,
            $actor,
            InventoryMovementType::Transfer,
            $idempotencyKey,
            $lines,
            $context,
        );
    }
}
