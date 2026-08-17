<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pricing\UpsertPriceListItemRequest;
use App\Services\Organization\CurrentOrganization;
use App\Services\Pricing\PriceListItemWriter;
use Illuminate\Http\JsonResponse;

class PriceListItemController extends Controller
{
    public function __invoke(
        UpsertPriceListItemRequest $request,
        string $priceListPublicId,
        string $packagingPublicId,
        CurrentOrganization $currentOrganization,
        PriceListItemWriter $writer,
    ): JsonResponse {
        $organization = $currentOrganization->for($request->user());

        $item = $writer->set(
            $organization,
            $request->user(),
            $priceListPublicId,
            $packagingPublicId,
            $request->validated(),
            (string) $request->header('X-Privileged-Reason'),
        );

        return response()->json([
            'data' => [
                'price_list_id' => $priceListPublicId,
                'product_packaging_id' => $packagingPublicId,
                'selling_price' => $item->selling_price,
                'minimum_selling_price' => $item->minimum_selling_price,
                'maximum_discount_percent' => $item->maximum_discount_percent,
            ],
        ]);
    }
}
