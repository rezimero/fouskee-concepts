<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreGoodsReceiptRequest;
use App\Http\Resources\GoodsReceiptResource;
use App\Models\GoodsReceipt;
use App\Services\Organization\CurrentOrganization;
use App\Services\Procurement\GoodsReceiptCreator;
use Illuminate\Http\Request;

class GoodsReceiptController extends Controller
{
    public function index(Request $request, CurrentOrganization $currentOrganization)
    {
        $organization = $currentOrganization->for($request->user());

        return GoodsReceiptResource::collection(
            GoodsReceipt::query()
                ->with(['purchaseOrder', 'location', 'movement'])
                ->where('organization_id', $organization->id)
                ->orderByDesc('id')
                ->paginate(max(1, min((int) $request->query('per_page', 25), 100))),
        );
    }

    public function store(
        StoreGoodsReceiptRequest $request,
        CurrentOrganization $currentOrganization,
        GoodsReceiptCreator $creator,
    ): GoodsReceiptResource {
        $organization = $currentOrganization->for($request->user());
        $data = $request->validated();

        return new GoodsReceiptResource($creator->create(
            $organization,
            $request->user(),
            $data['idempotency_key'],
            $data,
            $request->header('X-Privileged-Reason'),
        ));
    }

    public function show(
        Request $request,
        string $goodsReceiptPublicId,
        CurrentOrganization $currentOrganization,
    ): GoodsReceiptResource {
        $organization = $currentOrganization->for($request->user());

        return new GoodsReceiptResource(
            GoodsReceipt::query()
                ->with([
                    'purchaseOrder.supplier',
                    'location',
                    'lines.product',
                    'lines.packaging',
                    'lines.purchaseOrderLine',
                    'movement',
                ])
                ->where('organization_id', $organization->id)
                ->where('public_id', $goodsReceiptPublicId)
                ->firstOrFail(),
        );
    }
}
