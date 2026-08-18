<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseOrderResource;
use App\Services\Organization\CurrentOrganization;
use App\Services\Procurement\PurchaseOrderSubmitter;
use Illuminate\Http\Request;

class PurchaseOrderSubmitController extends Controller
{
    public function __invoke(
        Request $request,
        string $purchaseOrderPublicId,
        CurrentOrganization $currentOrganization,
        PurchaseOrderSubmitter $submitter,
    ): PurchaseOrderResource {
        $organization = $currentOrganization->for($request->user());

        return new PurchaseOrderResource($submitter->submit(
            $organization,
            $request->user(),
            $purchaseOrderPublicId,
        ));
    }
}
