<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSalesOrderRequest;
use App\Http\Resources\SalesOrderResource;
use App\Models\SalesOrder;
use App\Services\Organization\CurrentOrganization;
use App\Services\Sales\SalesOrderCreator;
use Illuminate\Http\Request;

class SalesOrderController extends Controller
{
    public function index(Request $request, CurrentOrganization $current)
    {
        $organization = $current->for($request->user());

        return SalesOrderResource::collection(
            SalesOrder::query()
                ->with('customer')
                ->where('organization_id', $organization->id)
                ->orderByDesc('id')
                ->paginate(max(1, min((int) $request->query('per_page', 25), 100))),
        );
    }

    public function store(
        StoreSalesOrderRequest $request,
        CurrentOrganization $current,
        SalesOrderCreator $creator,
    ): SalesOrderResource {
        $organization = $current->for($request->user());

        return new SalesOrderResource($creator->create(
            $organization,
            $request->user(),
            $request->idempotencyKey(),
            $request->validated(),
        ));
    }

    public function show(
        Request $request,
        string $salesOrderPublicId,
        CurrentOrganization $current,
    ): SalesOrderResource {
        $organization = $current->for($request->user());

        return new SalesOrderResource(
            SalesOrder::query()
                ->with(['customer', 'priceList', 'lines.product', 'lines.packaging'])
                ->where('organization_id', $organization->id)
                ->where('public_id', $salesOrderPublicId)
                ->firstOrFail(),
        );
    }
}
