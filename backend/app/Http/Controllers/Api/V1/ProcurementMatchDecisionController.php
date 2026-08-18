<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProcurementMatchRunResource;
use App\Services\Organization\CurrentOrganization;
use App\Services\Procurement\ProcurementMatchDecisionService;
use Illuminate\Http\Request;

class ProcurementMatchDecisionController extends Controller
{
    public function approve(
        Request $request,
        string $matchRunPublicId,
        CurrentOrganization $currentOrganization,
        ProcurementMatchDecisionService $service,
    ): ProcurementMatchRunResource {
        return $this->decide(
            $request,
            $matchRunPublicId,
            $currentOrganization,
            $service,
            true,
        );
    }

    public function reject(
        Request $request,
        string $matchRunPublicId,
        CurrentOrganization $currentOrganization,
        ProcurementMatchDecisionService $service,
    ): ProcurementMatchRunResource {
        return $this->decide(
            $request,
            $matchRunPublicId,
            $currentOrganization,
            $service,
            false,
        );
    }

    private function decide(
        Request $request,
        string $publicId,
        CurrentOrganization $currentOrganization,
        ProcurementMatchDecisionService $service,
        bool $approve,
    ): ProcurementMatchRunResource {
        $organization = $currentOrganization->for($request->user());
        $reason = trim((string) $request->header('X-Privileged-Reason'));

        $run = $approve
            ? $service->approve($organization, $request->user(), $publicId, $reason)
            : $service->reject($organization, $request->user(), $publicId, $reason);

        return new ProcurementMatchRunResource($run);
    }
}
