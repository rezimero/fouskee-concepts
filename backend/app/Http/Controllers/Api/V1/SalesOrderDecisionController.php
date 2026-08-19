<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesOrderResource;
use App\Services\Organization\CurrentOrganization;
use App\Services\Sales\SalesOrderDecisionService;
use Illuminate\Http\Request;

class SalesOrderDecisionController extends Controller
{
    public function approve(
        Request $request,
        string $salesOrderPublicId,
        CurrentOrganization $current,
        SalesOrderDecisionService $service,
    ): SalesOrderResource {
        return $this->decide(
            $request,
            $salesOrderPublicId,
            $current,
            $service,
            true,
        );
    }

    public function reject(
        Request $request,
        string $salesOrderPublicId,
        CurrentOrganization $current,
        SalesOrderDecisionService $service,
    ): SalesOrderResource {
        return $this->decide(
            $request,
            $salesOrderPublicId,
            $current,
            $service,
            false,
        );
    }

    private function decide(
        Request $request,
        string $publicId,
        CurrentOrganization $current,
        SalesOrderDecisionService $service,
        bool $approve,
    ): SalesOrderResource {
        $organization = $current->for($request->user());
        $reason = trim((string) $request->header('X-Privileged-Reason'));

        $order = $approve
            ? $service->approve(
                $organization,
                $request->user(),
                $publicId,
                $reason,
            )
            : $service->reject(
                $organization,
                $request->user(),
                $publicId,
                $reason,
            );

        return new SalesOrderResource($order);
    }
}
