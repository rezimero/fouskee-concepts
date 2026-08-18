<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\PostAdjustmentRequest;
use App\Http\Resources\InventoryMovementResource;
use App\Services\Inventory\InventoryAdjustmentService;
use App\Services\Organization\CurrentOrganization;

class InventoryAdjustmentController extends Controller
{
    public function store(
        PostAdjustmentRequest $request,
        CurrentOrganization $currentOrganization,
        InventoryAdjustmentService $service,
    ): InventoryMovementResource {
        $organization = $currentOrganization->for($request->user());
        $data = $request->validated();

        return new InventoryMovementResource($service->post(
            $organization,
            $request->user(),
            $data['idempotency_key'],
            $data['location_id'],
            $data['lines'],
            trim((string) $request->header('X-Privileged-Reason')),
            $data['occurred_at'] ?? null,
        ));
    }
}
