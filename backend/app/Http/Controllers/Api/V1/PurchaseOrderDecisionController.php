<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseOrderResource;
use App\Services\Organization\CurrentOrganization;
use App\Services\Procurement\PurchaseOrderDecisionService;
use Illuminate\Http\Request;

class PurchaseOrderDecisionController extends Controller
{
    public function approve(
        Request $request,
        string $purchaseOrderPublicId,
        CurrentOrganization $currentOrganization,
        PurchaseOrderDecisionService $service,
    ): PurchaseOrderResource {
        return $this->decide(
            $request,
            $purchaseOrderPublicId,
            $currentOrganization,
            $service,
            true,
        );
    }

    public function reject(
        Request $request,
        string $purchaseOrderPublicId,
        CurrentOrganization $currentOrganization,
        PurchaseOrderDecisionService $service,
    ): PurchaseOrderResource {
        return $this->decide(
            $request,
            $purchaseOrderPublicId,
            $currentOrganization,
            $service,
            false,
        );
    }

    private function decide(
        Request $request,
        string $publicId,
        CurrentOrganization $currentOrganization,
        PurchaseOrderDecisionService $service,
        bool $approve,
    ): PurchaseOrderResource {
        $organization = $currentOrganization->for($request->user());
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

        return new PurchaseOrderResource($order);
    }
}
