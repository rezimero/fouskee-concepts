<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryMovementResource;
use App\Models\InventoryMovement;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\Request;

class InventoryMovementController extends Controller
{
    public function index(
        Request $request,
        CurrentOrganization $currentOrganization,
    ) {
        $organization = $currentOrganization->for($request->user());

        return InventoryMovementResource::collection(
            InventoryMovement::query()
                ->with([
                    'lines.location',
                    'lines.product',
                    'lines.packaging',
                    'lines.lot',
                ])
                ->where('organization_id', $organization->id)
                ->orderByDesc('id')
                ->paginate(max(1, min((int) $request->query('per_page', 25), 100))),
        );
    }

    public function show(
        Request $request,
        string $movementPublicId,
        CurrentOrganization $currentOrganization,
    ): InventoryMovementResource {
        $organization = $currentOrganization->for($request->user());

        return new InventoryMovementResource(
            InventoryMovement::query()
                ->with([
                    'lines.location',
                    'lines.product',
                    'lines.packaging',
                    'lines.lot',
                    'reversalOf',
                ])
                ->where('organization_id', $organization->id)
                ->where('public_id', $movementPublicId)
                ->firstOrFail(),
        );
    }
}
