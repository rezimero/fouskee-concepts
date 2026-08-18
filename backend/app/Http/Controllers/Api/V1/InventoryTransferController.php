<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\PostTransferRequest;
use App\Http\Resources\InventoryMovementResource;
use App\Services\Inventory\InventoryTransferService;
use App\Services\Organization\CurrentOrganization;

class InventoryTransferController extends Controller
{
    public function store(
        PostTransferRequest $request,
        CurrentOrganization $currentOrganization,
        InventoryTransferService $service,
    ): InventoryMovementResource {
        $organization = $currentOrganization->for($request->user());
        $data = $request->validated();

        return new InventoryMovementResource($service->post(
            $organization,
            $request->user(),
            $data['idempotency_key'],
            $data['source_location_id'],
            $data['destination_location_id'],
            $data['lines'],
            $data['occurred_at'] ?? null,
            $request->header('X-Privileged-Reason'),
        ));
    }
}
