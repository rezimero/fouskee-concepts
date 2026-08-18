<?php

namespace App\Services\Inventory;

use App\Models\InventoryLocation;
use App\Models\Organization;

class InventoryLocationResolver
{
    public function active(
        Organization $organization,
        string $publicId,
    ): InventoryLocation {
        return InventoryLocation::query()
            ->where('organization_id', $organization->id)
            ->where('public_id', $publicId)
            ->where('status', 'active')
            ->firstOrFail();
    }
}
