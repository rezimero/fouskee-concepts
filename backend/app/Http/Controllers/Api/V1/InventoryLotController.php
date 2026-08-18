<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryLot;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\Request;

class InventoryLotController extends Controller
{
    public function __invoke(
        Request $request,
        CurrentOrganization $currentOrganization,
    ) {
        $organization = $currentOrganization->for($request->user());

        $query = InventoryLot::query()
            ->with('product')
            ->where('organization_id', $organization->id)
            ->orderByDesc('id');

        if ($productId = $request->query('product_id')) {
            $query->whereHas(
                'product',
                fn ($builder) => $builder->where('public_id', $productId),
            );
        }

        return response()->json([
            'data' => $query->limit(200)->get()->map(fn ($lot) => [
                'id' => $lot->public_id,
                'code' => $lot->code,
                'product_id' => $lot->product?->public_id,
                'product_name' => $lot->product?->name,
                'expiry_date' => $lot->expiry_date?->toDateString(),
                'status' => $lot->status->value,
            ]),
        ]);
    }
}
