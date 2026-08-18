<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StorePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Services\Organization\CurrentOrganization;
use App\Services\Procurement\PurchaseOrderCreator;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function index(Request $request, CurrentOrganization $currentOrganization)
    {
        $organization = $currentOrganization->for($request->user());

        return PurchaseOrderResource::collection(
            PurchaseOrder::query()
                ->with('supplier')
                ->where('organization_id', $organization->id)
                ->orderByDesc('id')
                ->paginate(max(1, min((int) $request->query('per_page', 25), 100))),
        );
    }

    public function store(
        StorePurchaseOrderRequest $request,
        CurrentOrganization $currentOrganization,
        PurchaseOrderCreator $creator,
    ): PurchaseOrderResource {
        $organization = $currentOrganization->for($request->user());
        $data = $request->validated();

        return new PurchaseOrderResource($creator->create(
            $organization,
            $request->user(),
            $data['idempotency_key'],
            $data,
        ));
    }

    public function show(
        Request $request,
        string $purchaseOrderPublicId,
        CurrentOrganization $currentOrganization,
    ): PurchaseOrderResource {
        $organization = $currentOrganization->for($request->user());

        return new PurchaseOrderResource(
            PurchaseOrder::query()
                ->with([
                    'supplier',
                    'lines.product',
                    'lines.packaging',
                    'policyVersion',
                ])
                ->where('organization_id', $organization->id)
                ->where('public_id', $purchaseOrderPublicId)
                ->firstOrFail(),
        );
    }
}
