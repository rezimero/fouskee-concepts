<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\PostReceiptRequest;
use App\Http\Resources\InventoryMovementResource;
use App\Services\Inventory\InventoryReceiptService;
use App\Services\Organization\CurrentOrganization;

class InventoryReceiptController extends Controller
{
    public function store(
        PostReceiptRequest $request,
        CurrentOrganization $currentOrganization,
        InventoryReceiptService $service,
    ): InventoryMovementResource {
        $organization = $currentOrganization->for($request->user());
        $data = $request->validated();

        return new InventoryMovementResource($service->post(
            $organization,
            $request->user(),
            $data['idempotency_key'],
            $data['location_id'],
            $data['lines'],
        ));
    }
}
