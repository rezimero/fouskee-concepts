<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\InventoryLocationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryLocationRequest;
use App\Models\InventoryLocation;
use App\Services\Audit\AuditRecorder;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryLocationController extends Controller
{
    public function index(Request $request, CurrentOrganization $currentOrganization)
    {
        $organization = $currentOrganization->for($request->user());

        return response()->json(['data' => InventoryLocation::query()
            ->where('organization_id', $organization->id)
            ->orderBy('name')
            ->get()
            ->map(fn ($location) => $this->data($location))]);
    }

    public function store(
        StoreInventoryLocationRequest $request,
        CurrentOrganization $currentOrganization,
        AuditRecorder $audit,
    ): JsonResponse {
        $organization = $currentOrganization->for($request->user());

        $location = InventoryLocation::query()->create([
            'organization_id' => $organization->id,
            ...$request->validated(),
            'status' => InventoryLocationStatus::Active,
        ]);

        $audit->record(
            event: 'inventory.location.created',
            actor: $request->user(),
            subject: $location,
            organizationId: $organization->id,
            after: $this->data($location),
        );

        return response()->json(['data' => $this->data($location)], 201);
    }

    private function data(InventoryLocation $location): array
    {
        return [
            'id' => $location->public_id,
            'code' => $location->code,
            'name' => $location->name,
            'type' => $location->type->value,
            'status' => $location->status->value,
        ];
    }
}
