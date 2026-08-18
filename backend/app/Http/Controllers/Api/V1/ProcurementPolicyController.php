<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\UpdateProcurementPolicyRequest;
use App\Services\Organization\CurrentOrganization;
use App\Services\Procurement\ProcurementPolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProcurementPolicyController extends Controller
{
    public function show(
        Request $request,
        CurrentOrganization $currentOrganization,
        ProcurementPolicyService $policies,
    ): JsonResponse {
        $organization = $currentOrganization->for($request->user());

        return response()->json([
            'data' => $this->data($policies->current($organization)),
        ]);
    }

    public function update(
        UpdateProcurementPolicyRequest $request,
        CurrentOrganization $currentOrganization,
        ProcurementPolicyService $policies,
    ): JsonResponse {
        $organization = $currentOrganization->for($request->user());
        $policy = $policies->createVersion(
            $organization,
            $request->user(),
            $request->validated(),
            trim((string) $request->header('X-Privileged-Reason')),
        );

        return response()->json(['data' => $this->data($policy)], 201);
    }

    private function data($policy): array
    {
        return [
            'id' => $policy->public_id,
            'effective_from' => $policy->effective_from?->toISOString(),
            'purchase_order_approval_value_threshold' => $policy->purchase_order_approval_value_threshold,
            'over_receipt_tolerance_percent' => $policy->over_receipt_tolerance_percent,
            'invoice_quantity_tolerance_percent' => $policy->invoice_quantity_tolerance_percent,
            'invoice_price_tolerance_percent' => $policy->invoice_price_tolerance_percent,
            'invoice_amount_tolerance_percent' => $policy->invoice_amount_tolerance_percent,
        ];
    }
}
