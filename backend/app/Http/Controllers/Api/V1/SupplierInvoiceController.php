<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreSupplierInvoiceRequest;
use App\Http\Resources\SupplierInvoiceResource;
use App\Models\SupplierInvoice;
use App\Services\Organization\CurrentOrganization;
use App\Services\Procurement\SupplierInvoiceCreator;
use Illuminate\Http\Request;

class SupplierInvoiceController extends Controller
{
    public function index(Request $request, CurrentOrganization $currentOrganization)
    {
        $organization = $currentOrganization->for($request->user());

        return SupplierInvoiceResource::collection(
            SupplierInvoice::query()
                ->with(['supplier', 'latestMatchRun'])
                ->where('organization_id', $organization->id)
                ->orderByDesc('id')
                ->paginate(max(1, min((int) $request->query('per_page', 25), 100))),
        );
    }

    public function store(
        StoreSupplierInvoiceRequest $request,
        CurrentOrganization $currentOrganization,
        SupplierInvoiceCreator $creator,
    ): SupplierInvoiceResource {
        $organization = $currentOrganization->for($request->user());
        $data = $request->validated();

        return new SupplierInvoiceResource($creator->create(
            $organization,
            $request->user(),
            $data['idempotency_key'],
            $data,
        ));
    }

    public function show(
        Request $request,
        string $supplierInvoicePublicId,
        CurrentOrganization $currentOrganization,
    ): SupplierInvoiceResource {
        $organization = $currentOrganization->for($request->user());

        return new SupplierInvoiceResource(
            SupplierInvoice::query()
                ->with([
                    'supplier',
                    'lines.product',
                    'lines.purchaseOrderLine',
                    'latestMatchRun.lines',
                ])
                ->where('organization_id', $organization->id)
                ->where('public_id', $supplierInvoicePublicId)
                ->firstOrFail(),
        );
    }
}
