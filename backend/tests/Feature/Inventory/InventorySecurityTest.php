<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryLocation;
use App\Models\Organization;
use Tests\Support\InventoryTestCase;

class InventorySecurityTest extends InventoryTestCase
{
    public function test_location_listing_is_organization_scoped(): void
    {
        $visible = $this->location('VISIBLE');

        $other = Organization::query()->create([
            'code' => 'INV-OTHER',
            'legal_name' => 'Inventory Other',
            'trading_name' => 'Inventory Other',
            'country_code' => 'NG',
            'currency_code' => 'NGN',
            'timezone' => 'Africa/Lagos',
            'status' => 'active',
        ]);

        InventoryLocation::query()->create([
            'organization_id' => $other->id,
            'code' => 'HIDDEN',
            'name' => 'Hidden Location',
            'type' => 'warehouse',
            'status' => 'active',
        ]);

        $this->getJson('/api/v1/inventory/locations')
            ->assertOk()
            ->assertJsonFragment(['id' => $visible->public_id])
            ->assertJsonMissing(['code' => 'HIDDEN']);
    }

    public function test_reserved_stock_cannot_be_transferred_away(): void
    {
        $source = $this->location('RESERVED');
        $destination = $this->location('RES-TARGET');
        $this->receipt($source, 1)->assertSuccessful();

        $this->postJson('/api/v1/inventory/reservations', [
            'location_id' => $source->public_id,
            'product_id' => $this->product->public_id,
            'quantity_base' => 40,
        ])->assertCreated();

        $this->withHeader('Idempotency-Key', 'reserved-transfer-key')
            ->postJson('/api/v1/inventory/transfers', [
                'source_location_id' => $source->public_id,
                'destination_location_id' => $destination->public_id,
                'lines' => [[
                    'product_id' => $this->product->public_id,
                    'packaging_id' => null,
                    'quantity' => 20,
                ]],
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'inventory_insufficient_stock');
    }
}
