<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesOrderResource;
use App\Services\Organization\CurrentOrganization;
use App\Services\Sales\SalesOrderSubmitter;
use Illuminate\Http\Request;

class SalesOrderSubmitController extends Controller
{
    public function __invoke(
        Request $request,
        string $salesOrderPublicId,
        CurrentOrganization $current,
        SalesOrderSubmitter $submitter,
    ): SalesOrderResource {
        $organization = $current->for($request->user());

        return new SalesOrderResource($submitter->submit(
            $organization,
            $request->user(),
            $salesOrderPublicId,
        ));
    }
}
