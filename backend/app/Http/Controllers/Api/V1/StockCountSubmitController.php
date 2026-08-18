<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StockCountSubmitRequest;
use App\Http\Resources\StockCountResource;
use App\Services\Inventory\StockCountSubmitter;
use App\Services\Organization\CurrentOrganization;

class StockCountSubmitController extends Controller
{
    public function __invoke(
        StockCountSubmitRequest $request,
        string $countPublicId,
        CurrentOrganization $currentOrganization,
        StockCountSubmitter $submitter,
    ): StockCountResource {
        $organization = $currentOrganization->for($request->user());

        return new StockCountResource($submitter->submit(
            $organization,
            $request->user(),
            $countPublicId,
            $request->validated('idempotency_key'),
            trim((string) $request->header('X-Privileged-Reason')),
        ));
    }
}
