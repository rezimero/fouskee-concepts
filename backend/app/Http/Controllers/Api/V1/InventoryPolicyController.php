<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\UpdateInventoryPolicyRequest;
use App\Services\Inventory\InventoryPolicyService;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryPolicyController extends Controller
{
    public function show(
        Request $request,
        CurrentOrganization $currentOrganization,
        InventoryPolicyService $policies,
    ): JsonResponse {
        $organization = $currentOrganization->for($request->user());

        return response()->json([
            'data' => $this->data($policies->for($organization)),
        ]);
    }

    public function update(
        UpdateInventoryPolicyRequest $request,
        CurrentOrganization $currentOrganization,
        InventoryPolicyService $policies,
    ): JsonResponse {
        $organization = $currentOrganization->for($request->user());
        $policy = $policies->update(
            $organization,
            $request->user(),
            $request->validated(),
            trim((string) $request->header('X-Privileged-Reason')),
        );

        return response()->json(['data' => $this->data($policy)]);
    }

    private function data($policy): array
    {
        return [
            'count_variance_approval_value_threshold' => $policy->count_variance_approval_value_threshold,
            'backdate_window_days' => $policy->backdate_window_days,
            'require_separate_transfer_receiver' => $policy->require_separate_transfer_receiver,
            'allow_immediate_transfers' => $policy->allow_immediate_transfers,
        ];
    }
}
