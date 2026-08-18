<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryBalance;
use Tests\Support\InventoryTestCase;

class InventoryValuationTest extends InventoryTestCase
{
    public function test_receipts_maintain_perpetual_weighted_average_cost(): void
    {
        $location = $this->location('VALUE');

        $this->receipt($location, 2, 'value-receipt-1', 1000)->assertSuccessful();
        $this->receipt($location, 1, 'value-receipt-2', 2000)->assertSuccessful();

        $balance = InventoryBalance::query()
            ->where('inventory_location_id', $location->id)
            ->where('product_id', $this->product->id)
            ->firstOrFail();

        $this->assertSame('150.000000', $balance->quantity_on_hand_base);
        $this->assertSame('200000.000000', $balance->inventory_value);
        $this->assertSame('1333.333333', $balance->weighted_average_unit_cost);
    }

    public function test_transfer_conserves_inventory_value(): void
    {
        $source = $this->location('VALUE-SOURCE');
        $destination = $this->location('VALUE-DEST');
        $this->receipt($source, 2, 'value-source-receipt', 1000)->assertSuccessful();

        $this->withHeader('Idempotency-Key', 'value-transfer-key')
            ->postJson('/api/v1/inventory/transfers', [
                'source_location_id' => $source->public_id,
                'destination_location_id' => $destination->public_id,
                'lines' => [[
                    'product_id' => $this->product->public_id,
                    'packaging_id' => $this->packaging->public_id,
                    'quantity' => 1,
                ]],
            ])->assertSuccessful();

        $balances = InventoryBalance::query()
            ->where('product_id', $this->product->id)
            ->get()
            ->keyBy('inventory_location_id');

        $this->assertSame('50000.000000', $balances[$source->id]->inventory_value);
        $this->assertSame('50000.000000', $balances[$destination->id]->inventory_value);
        $this->assertEquals(
            100000.0,
            InventoryBalance::query()
                ->where('product_id', $this->product->id)
                ->sum('inventory_value'),
        );
    }

    public function test_receipt_requires_unit_cost(): void
    {
        $location = $this->location('NO-COST');

        $this->withHeader('Idempotency-Key', 'no-cost-receipt-key')
            ->postJson('/api/v1/inventory/receipts', [
                'location_id' => $location->public_id,
                'lines' => [[
                    'product_id' => $this->product->public_id,
                    'quantity' => 10,
                ]],
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('lines.0.unit_cost_base');
    }
}
