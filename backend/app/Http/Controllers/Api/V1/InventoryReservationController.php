<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\CreateReservationRequest;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\JsonResponse;

class InventoryReservationController extends Controller
{
    public function store(
        CreateReservationRequest $request,
        CurrentOrganization $currentOrganization,
        InventoryReservationService $service,
    ): JsonResponse {
        $organization = $currentOrganization->for($request->user());
        $reservation = $service->create(
            $organization,
            $request->user(),
            $request->validated(),
        );

        return response()->json(['data' => [
            'id' => $reservation->public_id,
            'quantity_base' => $reservation->quantity_base,
            'status' => $reservation->status->value,
            'expires_at' => $reservation->expires_at?->toISOString(),
        ]], 201);
    }
}
