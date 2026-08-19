<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSalesQuotationRequest;
use App\Http\Resources\SalesQuotationResource;
use App\Models\SalesQuotation;
use App\Services\Organization\CurrentOrganization;
use App\Services\Sales\SalesQuotationCreator;
use Illuminate\Http\Request;

class SalesQuotationController extends Controller
{
    public function index(Request $request, CurrentOrganization $current)
    {
        $organization = $current->for($request->user());

        return SalesQuotationResource::collection(
            SalesQuotation::query()
                ->with('customer')
                ->where('organization_id', $organization->id)
                ->orderByDesc('id')
                ->paginate(max(1, min((int) $request->query('per_page', 25), 100))),
        );
    }

    public function store(
        StoreSalesQuotationRequest $request,
        CurrentOrganization $current,
        SalesQuotationCreator $creator,
    ): SalesQuotationResource {
        $organization = $current->for($request->user());

        return new SalesQuotationResource($creator->create(
            $organization,
            $request->user(),
            $request->idempotencyKey(),
            $request->validated(),
        ));
    }

    public function show(
        Request $request,
        string $quotationPublicId,
        CurrentOrganization $current,
    ): SalesQuotationResource {
        $organization = $current->for($request->user());

        return new SalesQuotationResource(
            SalesQuotation::query()
                ->with(['customer', 'priceList', 'lines'])
                ->where('organization_id', $organization->id)
                ->where('public_id', $quotationPublicId)
                ->firstOrFail(),
        );
    }
}
