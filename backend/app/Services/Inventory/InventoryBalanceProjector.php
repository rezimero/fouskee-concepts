<?php

namespace App\Services\Inventory;

use App\Models\InventoryBalance;

class InventoryBalanceProjector
{
    public function __construct(
        private readonly InventoryQuantityMath $math,
    ) {}

    public function apply(
        InventoryBalance $balance,
        string $quantityDelta,
        string $valueDelta,
    ): void {
        $nextQuantity = $this->math->add(
            $balance->quantity_on_hand_base,
            $quantityDelta,
        );
        $nextValue = $this->math->add($balance->inventory_value, $valueDelta);

        if ($this->math->isZero($nextQuantity)) {
            $nextValue = '0.000000';
            $weightedAverage = '0.000000';
        } else {
            $weightedAverage = $this->math->divide($nextValue, $nextQuantity);
        }

        InventoryBalance::query()
            ->whereKey($balance->id)
            ->update([
                'quantity_on_hand_base' => $nextQuantity,
                'inventory_value' => $nextValue,
                'weighted_average_unit_cost' => $weightedAverage,
                'version' => $balance->version + 1,
                'projected_at' => now(),
            ]);

        $balance->forceFill([
            'quantity_on_hand_base' => $nextQuantity,
            'inventory_value' => $nextValue,
            'weighted_average_unit_cost' => $weightedAverage,
            'version' => $balance->version + 1,
            'projected_at' => now(),
        ]);
    }
}
