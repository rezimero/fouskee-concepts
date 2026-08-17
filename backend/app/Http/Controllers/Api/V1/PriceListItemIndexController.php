<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PriceList;
use App\Models\PriceListItem;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceListItemIndexController extends Controller
{
    public function __invoke(
        Request $request,
        string $priceListPublicId,
        CurrentOrganization $currentOrganization,
    ): JsonResponse {
        $organization = $currentOrganization->for($request->user());

        $priceList = PriceList::query()
            ->where('organization_id', $organization->id)
            ->where('public_id', $priceListPublicId)
            ->firstOrFail();

        $items = PriceListItem::query()
            ->with(['packaging.product'])
            ->where('price_list_id', $priceList->id)
            ->get()
            ->sortBy(fn ($item) => $item->packaging->product->name)
            ->values();

        return response()->json([
            'data' => $items->map(fn ($item) => [
                'product_packaging_id' => $item->packaging->public_id,
                'product_name' => $item->packaging->product->name,
                'packaging_name' => $item->packaging->name,
                'selling_price' => $item->selling_price,
                'minimum_selling_price' => $item->minimum_selling_price,
                'maximum_discount_percent' => $item->maximum_discount_percent,
            ]),
        ]);
    }
}
