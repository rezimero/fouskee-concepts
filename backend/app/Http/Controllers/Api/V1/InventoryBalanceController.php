<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryBalance;
use App\Services\Inventory\InventoryBalanceService;
use App\Services\Inventory\InventoryQuantityMath;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\Request;

class InventoryBalanceController extends Controller
{
    public function __invoke(
        Request $request,
        CurrentOrganization $currentOrganization,
        InventoryBalanceService $balances,
        InventoryQuantityMath $math,
    ) {
        $organization = $currentOrganization->for($request->user());

        $rows = InventoryBalance::query()
            ->with(['location', 'product', 'lot'])
            ->where('organization_id', $organization->id)
            ->orderBy('inventory_location_id')
            ->orderBy('product_id')
            ->get()
            ->map(function ($row) use ($balances, $math, $organization) {
                $reserved = $balances->reservedBase(
                    $organization->id,
                    $row->inventory_location_id,
                    $row->product_id,
                    $row->inventory_lot_id,
                );

                return [
                    'location_id' => $row->location->public_id,
                    'product_id' => $row->product->public_id,
                    'lot_id' => $row->lot?->public_id,
                    'on_hand_base' => $row->quantity_on_hand_base,
                    'reserved_base' => $reserved,
                    'available_base' => $math->add(
                        $row->quantity_on_hand_base,
                        $math->negate($reserved),
                    ),
                    'inventory_value' => $row->inventory_value,
                    'weighted_average_unit_cost' => $row->weighted_average_unit_cost,
                ];
            });

        return response()->json(['data' => $rows]);
    }
}
