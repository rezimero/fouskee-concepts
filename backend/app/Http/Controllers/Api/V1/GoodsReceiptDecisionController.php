<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\GoodsReceiptResource;
use App\Services\Organization\CurrentOrganization;
use App\Services\Procurement\GoodsReceiptDecisionService;
use Illuminate\Http\Request;

class GoodsReceiptDecisionController extends Controller
{
    public function approve(
        Request $request,
        string $goodsReceiptPublicId,
        CurrentOrganization $currentOrganization,
        GoodsReceiptDecisionService $service,
    ): GoodsReceiptResource {
        return $this->decide(
            $request,
            $goodsReceiptPublicId,
            $currentOrganization,
            $service,
            true,
        );
    }

    public function reject(
        Request $request,
        string $goodsReceiptPublicId,
        CurrentOrganization $currentOrganization,
        GoodsReceiptDecisionService $service,
    ): GoodsReceiptResource {
        return $this->decide(
            $request,
            $goodsReceiptPublicId,
            $currentOrganization,
            $service,
            false,
        );
    }

    private function decide(
        Request $request,
        string $publicId,
        CurrentOrganization $currentOrganization,
        GoodsReceiptDecisionService $service,
        bool $approve,
    ): GoodsReceiptResource {
        $organization = $currentOrganization->for($request->user());
        $reason = trim((string) $request->header('X-Privileged-Reason'));

        $receipt = $approve
            ? $service->approve($organization, $request->user(), $publicId, $reason)
            : $service->reject($organization, $request->user(), $publicId, $reason);

        return new GoodsReceiptResource($receipt);
    }
}
