<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ReverseMovementRequest;
use App\Http\Resources\InventoryMovementResource;
use App\Services\Inventory\MovementReversalService;
use App\Services\Organization\CurrentOrganization;

class InventoryReversalController extends Controller
{
    public function store(
        ReverseMovementRequest $request,
        string $movementPublicId,
        CurrentOrganization $currentOrganization,
        MovementReversalService $service,
    ): InventoryMovementResource {
        $organization = $currentOrganization->for($request->user());
        $data = $request->validated();

        return new InventoryMovementResource($service->reverse(
            $organization,
            $request->user(),
            $movementPublicId,
            $data['idempotency_key'],
            trim((string) $request->header('X-Privileged-Reason')),
        ));
    }
}
