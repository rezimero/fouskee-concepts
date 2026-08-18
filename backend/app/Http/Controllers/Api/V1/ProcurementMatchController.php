<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProcurementMatchRunResource;
use App\Models\ProcurementMatchRun;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\Request;

class ProcurementMatchController extends Controller
{
    public function show(
        Request $request,
        string $matchRunPublicId,
        CurrentOrganization $currentOrganization,
    ): ProcurementMatchRunResource {
        $organization = $currentOrganization->for($request->user());

        return new ProcurementMatchRunResource(
            ProcurementMatchRun::query()
                ->with([
                    'invoice.supplier',
                    'lines.invoiceLine.product',
                    'lines.invoiceLine.purchaseOrderLine',
                ])
                ->where('organization_id', $organization->id)
                ->where('public_id', $matchRunPublicId)
                ->firstOrFail(),
        );
    }
}
