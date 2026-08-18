<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ReverseGoodsReceiptRequest;
use App\Http\Resources\GoodsReceiptResource;
use App\Services\Organization\CurrentOrganization;
use App\Services\Procurement\GoodsReceiptReversalService;

class GoodsReceiptReversalController extends Controller
{
    public function __invoke(
        ReverseGoodsReceiptRequest $request,
        string $goodsReceiptPublicId,
        CurrentOrganization $currentOrganization,
        GoodsReceiptReversalService $service,
    ): GoodsReceiptResource {
        $organization = $currentOrganization->for($request->user());

        return new GoodsReceiptResource($service->reverse(
            $organization,
            $request->user(),
            $goodsReceiptPublicId,
            $request->validated('idempotency_key'),
            trim((string) $request->header('X-Privileged-Reason')),
        ));
    }
}
