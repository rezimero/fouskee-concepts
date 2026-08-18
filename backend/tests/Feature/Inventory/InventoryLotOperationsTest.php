<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryBalance;
use App\Models\InventoryLot;
use Tests\Support\InventoryTestCase;

class InventoryLotOperationsTest extends InventoryTestCase
{
    public function test_receipt_can_create_lot_and_project_lot_balance(): void
    {
        $location = $this->location('LOT-REC');

        $this->receipt(
            $location,
            1,
            'lot-receipt-key',
            1200,
            ['lot_code' => 'LOT-A', 'expiry_date' => '2027-12-31'],
        )->assertSuccessful();

        $lot = InventoryLot::query()->where('code', 'LOT-A')->firstOrFail();

        $balance = InventoryBalance::query()
            ->where('inventory_lot_id', $lot->id)
            ->firstOrFail();

        $this->assertSame('50.000000', $balance->quantity_on_hand_base);
        $this->assertSame('60000.000000', $balance->inventory_value);
    }

    public function test_lot_specific_stock_can_transfer_and_reserve(): void
    {
        $source = $this->location('LOT-SRC');
        $destination = $this->location('LOT-DST');

        $this->receipt(
            $source,
            2,
            'lot-source-key',
            1000,
            ['lot_code' => 'LOT-B'],
        )->assertSuccessful();

        $lot = InventoryLot::query()->where('code', 'LOT-B')->firstOrFail();

        $this->withHeader('Idempotency-Key', 'lot-transfer-key')
            ->postJson('/api/v1/inventory/transfers', [
                'source_location_id' => $source->public_id,
                'destination_location_id' => $destination->public_id,
                'lines' => [[
                    'product_id' => $this->product->public_id,
                    'lot_id' => $lot->public_id,
                    'quantity' => 20,
                ]],
            ])->assertSuccessful();

        $this->postJson('/api/v1/inventory/reservations', [
            'location_id' => $destination->public_id,
            'product_id' => $this->product->public_id,
            'lot_id' => $lot->public_id,
            'quantity_base' => 10,
        ])->assertCreated();

        $this->getJson('/api/v1/inventory/balances')
            ->assertOk()
            ->assertJsonFragment([
                'lot_id' => $lot->public_id,
                'reserved_base' => '10.000000',
            ]);
    }
}
