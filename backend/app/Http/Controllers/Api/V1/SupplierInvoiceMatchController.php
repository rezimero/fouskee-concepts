<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\MatchSupplierInvoiceRequest;
use App\Http\Resources\ProcurementMatchRunResource;
use App\Services\Organization\CurrentOrganization;
use App\Services\Procurement\ThreeWayMatchService;

class SupplierInvoiceMatchController extends Controller
{
    public function __invoke(
        MatchSupplierInvoiceRequest $request,
        string $supplierInvoicePublicId,
        CurrentOrganization $currentOrganization,
        ThreeWayMatchService $service,
    ): ProcurementMatchRunResource {
        $organization = $currentOrganization->for($request->user());

        return new ProcurementMatchRunResource($service->match(
            $organization,
            $request->user(),
            $supplierInvoicePublicId,
            $request->validated('idempotency_key'),
        ));
    }
}
