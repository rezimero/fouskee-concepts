<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryBalance;
use Tests\Support\InventoryTestCase;

class InventoryTransferTest extends InventoryTestCase
{
    public function test_transfer_conserves_base_quantity(): void
    {
        $source = $this->location('SOURCE');
        $destination = $this->location('DEST');
        $this->receipt($source, 2)->assertSuccessful();

        $this->withHeader('Idempotency-Key', 'transfer-key-0001')
            ->postJson('/api/v1/inventory/transfers', [
                'source_location_id' => $source->public_id,
                'destination_location_id' => $destination->public_id,
                'lines' => [[
                    'product_id' => $this->product->public_id,
                    'packaging_id' => $this->packaging->public_id,
                    'quantity' => 1,
                ]],
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.type', 'transfer');

        $balances = InventoryBalance::query()
            ->where('product_id', $this->product->id)
            ->pluck('quantity_on_hand_base', 'inventory_location_id');

        $this->assertSame('50.000000', $balances[$source->id]);
        $this->assertSame('50.000000', $balances[$destination->id]);
    }

    public function test_transfer_cannot_create_negative_stock(): void
    {
        $source = $this->location('EMPTY');
        $destination = $this->location('TARGET');

        $this->withHeader('Idempotency-Key', 'negative-key-0001')
            ->postJson('/api/v1/inventory/transfers', [
                'source_location_id' => $source->public_id,
                'destination_location_id' => $destination->public_id,
                'lines' => [[
                    'product_id' => $this->product->public_id,
                    'packaging_id' => $this->packaging->public_id,
                    'quantity' => 1,
                ]],
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'inventory_insufficient_stock');

        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_multiple_lines_cannot_bypass_negative_stock_guard(): void
    {
        $source = $this->location('MULTI');
        $destination = $this->location('MULTI-DEST');
        $this->receipt($source, 1)->assertSuccessful();

        $line = [
            'product_id' => $this->product->public_id,
            'packaging_id' => null,
            'quantity' => 30,
        ];

        $this->withHeader('Idempotency-Key', 'multi-line-negative-key')
            ->postJson('/api/v1/inventory/transfers', [
                'source_location_id' => $source->public_id,
                'destination_location_id' => $destination->public_id,
                'lines' => [$line, $line],
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'inventory_insufficient_stock');
    }
}
