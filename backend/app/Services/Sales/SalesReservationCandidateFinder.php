<?php

namespace App\Services\Sales;

use App\Models\InventoryBalance;
use App\Models\InventoryLocation;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Support\Collection;

class SalesReservationCandidateFinder
{
    public function for(
        Organization $organization,
        InventoryLocation $location,
        Product $product,
    ): Collection {
        return InventoryBalance::query()
            ->select('inventory_balances.*')
            ->leftJoin(
                'inventory_lots',
                'inventory_lots.id',
                '=',
                'inventory_balances.inventory_lot_id',
            )
            ->with('lot')
            ->where('inventory_balances.organization_id', $organization->id)
            ->where('inventory_balances.inventory_location_id', $location->id)
            ->where('inventory_balances.product_id', $product->id)
            ->where('inventory_balances.quantity_on_hand_base', '>', 0)
            ->orderByRaw(
                'CASE WHEN inventory_lots.expiry_date IS NULL THEN 1 ELSE 0 END',
            )
            ->orderBy('inventory_lots.expiry_date')
            ->orderByRaw(
                'CASE WHEN inventory_lots.received_at IS NULL THEN 1 ELSE 0 END',
            )
            ->orderBy('inventory_lots.received_at')
            ->orderBy('inventory_balances.id')
            ->get();
    }
}
