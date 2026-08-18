<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;

class InventoryProjectionSnapshot
{
    public function ledger(int $organizationId): array
    {
        return collect(DB::select(
            'SELECT ml.inventory_location_id, ml.product_id, ml.inventory_lot_id, '
            .'SUM(ml.quantity_base) AS quantity_on_hand_base, '
            .'SUM(COALESCE(ml.inventory_value_delta, 0)) AS inventory_value '
            .'FROM inventory_movement_lines ml '
            .'JOIN inventory_movements m ON m.id = ml.inventory_movement_id '
            .'WHERE m.organization_id = ? '
            .'GROUP BY ml.inventory_location_id, ml.product_id, ml.inventory_lot_id',
            [$organizationId],
        ))->keyBy(fn ($row) => $this->key(
            $row->inventory_location_id,
            $row->product_id,
            $row->inventory_lot_id,
        ))->all();
    }

    public function projection(int $organizationId): array
    {
        return DB::table('inventory_balances')
            ->where('organization_id', $organizationId)
            ->get()
            ->keyBy(fn ($row) => $this->key(
                $row->inventory_location_id,
                $row->product_id,
                $row->inventory_lot_id,
            ))
            ->all();
    }

    private function key(int $locationId, int $productId, ?int $lotId): string
    {
        return "{$locationId}-{$productId}-".($lotId ?? 'none');
    }
}
