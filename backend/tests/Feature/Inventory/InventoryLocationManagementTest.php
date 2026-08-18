<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryLocation;
use Tests\Support\InventoryTestCase;

class InventoryLocationManagementTest extends InventoryTestCase
{
    public function test_authorized_user_can_create_active_warehouse(): void
    {
        $this->postJson('/api/v1/inventory/locations', [
            'code' => 'UAT-WH-A',
            'name' => 'UAT Main Warehouse',
            'type' => 'warehouse',
        ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'UAT-WH-A')
            ->assertJsonPath('data.name', 'UAT Main Warehouse')
            ->assertJsonPath('data.type', 'warehouse')
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('inventory_locations', [
            'organization_id' => $this->organization->id,
            'code' => 'UAT-WH-A',
            'name' => 'UAT Main Warehouse',
            'type' => 'warehouse',
            'status' => 'active',
        ]);
    }

    public function test_system_transit_code_is_reserved_from_manual_creation(): void
    {
        foreach (['__TRANSIT__', '__transit__', ' __Transit__ '] as $code) {
            $this->postJson('/api/v1/inventory/locations', [
                'code' => $code,
                'name' => 'Manual Transit',
                'type' => 'transit',
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('code');
        }

        $this->assertDatabaseMissing('inventory_locations', [
            'organization_id' => $this->organization->id,
            'code' => InventoryLocation::SYSTEM_TRANSIT_CODE,
        ]);
    }
}
