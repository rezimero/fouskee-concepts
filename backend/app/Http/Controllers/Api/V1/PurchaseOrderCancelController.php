<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseOrderResource;
use App\Services\Organization\CurrentOrganization;
use App\Services\Procurement\PurchaseOrderCancellationService;
use Illuminate\Http\Request;

class PurchaseOrderCancelController extends Controller
{
    public function __invoke(
        Request $request,
        string $purchaseOrderPublicId,
        CurrentOrganization $currentOrganization,
        PurchaseOrderCancellationService $service,
    ): PurchaseOrderResource {
        $organization = $currentOrganization->for($request->user());

        return new PurchaseOrderResource($service->cancel(
            $organization,
            $request->user(),
            $purchaseOrderPublicId,
            trim((string) $request->header('X-Privileged-Reason')),
        ));
    }
}
