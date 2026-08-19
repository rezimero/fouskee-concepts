<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalesOrderResource;
use App\Http\Resources\SalesQuotationResource;
use App\Services\Organization\CurrentOrganization;
use App\Services\Sales\SalesQuotationConverter;
use App\Services\Sales\SalesQuotationLifecycle;
use Illuminate\Http\Request;

class SalesQuotationLifecycleController extends Controller
{
    public function send(
        Request $request,
        string $quotationPublicId,
        CurrentOrganization $current,
        SalesQuotationLifecycle $lifecycle,
    ): SalesQuotationResource {
        return $this->quotation(
            $request, $quotationPublicId, $current, $lifecycle, 'send',
        );
    }

    public function accept(
        Request $request,
        string $quotationPublicId,
        CurrentOrganization $current,
        SalesQuotationLifecycle $lifecycle,
    ): SalesQuotationResource {
        return $this->quotation(
            $request, $quotationPublicId, $current, $lifecycle, 'accept',
        );
    }

    public function convert(
        Request $request,
        string $quotationPublicId,
        CurrentOrganization $current,
        SalesQuotationConverter $converter,
    ): SalesOrderResource {
        $organization = $current->for($request->user());

        return new SalesOrderResource($converter->convert(
            $organization,
            $request->user(),
            $quotationPublicId,
        ));
    }

    private function quotation(
        Request $request,
        string $publicId,
        CurrentOrganization $current,
        SalesQuotationLifecycle $lifecycle,
        string $action,
    ): SalesQuotationResource {
        $organization = $current->for($request->user());

        return new SalesQuotationResource($lifecycle->{$action}(
            $organization,
            $request->user(),
            $publicId,
        ));
    }
}
