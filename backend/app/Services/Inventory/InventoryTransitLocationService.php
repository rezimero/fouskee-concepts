<?php

namespace App\Services\Inventory;

use App\Models\InventoryLocation;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditRecorder;

class InventoryTransitLocationService
{
    public function __construct(
        private readonly AuditRecorder $audit,
    ) {}

    public function for(Organization $organization, User $actor): InventoryLocation
    {
        $location = InventoryLocation::query()->firstOrCreate(
            [
                'organization_id' => $organization->id,
                'code' => InventoryLocation::SYSTEM_TRANSIT_CODE,
            ],
            [
                'name' => 'Controlled In-Transit Stock',
                'type' => 'transit',
                'status' => 'active',
            ],
        );

        if ($location->wasRecentlyCreated) {
            $this->audit->record(
                event: 'inventory.location.created',
                actor: $actor,
                subject: $location,
                organizationId: $organization->id,
                after: [
                    'code' => $location->code,
                    'type' => 'transit',
                    'system_generated' => true,
                ],
            );
        }

        return $location;
    }
}
