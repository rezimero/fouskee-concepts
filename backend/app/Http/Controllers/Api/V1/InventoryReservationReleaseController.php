<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryReservationReleaseController extends Controller
{
    public function __invoke(
        Request $request,
        string $reservationPublicId,
        CurrentOrganization $currentOrganization,
        InventoryReservationService $service,
    ): JsonResponse {
        $organization = $currentOrganization->for($request->user());
        $reservation = $service->release(
            $organization,
            $request->user(),
            $reservationPublicId,
        );

        return response()->json(['data' => [
            'id' => $reservation->public_id,
            'status' => $reservation->status->value,
            'released_at' => $reservation->released_at?->toISOString(),
        ]]);
    }
}
