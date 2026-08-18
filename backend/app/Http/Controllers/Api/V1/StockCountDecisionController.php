<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockCountDecisionRequest;
use App\Http\Requests\Inventory\StockCountRejectRequest;
use App\Http\Resources\StockCountResource;
use App\Services\Inventory\StockCountDecisionService;
use App\Services\Organization\CurrentOrganization;

class StockCountDecisionController extends Controller
{
    public function approve(
        StockCountDecisionRequest $request,
        string $countPublicId,
        CurrentOrganization $currentOrganization,
        StockCountDecisionService $service,
    ): StockCountResource {
        $organization = $currentOrganization->for($request->user());

        return new StockCountResource($service->approve(
            $organization,
            $request->user(),
            $countPublicId,
            $request->validated('idempotency_key'),
            trim((string) $request->header('X-Privileged-Reason')),
        ));
    }

    public function reject(
        StockCountRejectRequest $request,
        string $countPublicId,
        CurrentOrganization $currentOrganization,
        StockCountDecisionService $service,
    ): StockCountResource {
        $organization = $currentOrganization->for($request->user());

        return new StockCountResource($service->reject(
            $organization,
            $request->user(),
            $countPublicId,
            trim((string) $request->header('X-Privileged-Reason')),
        ));
    }
}
