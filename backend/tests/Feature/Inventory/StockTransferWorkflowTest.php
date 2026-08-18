<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryBalance;
use App\Models\InventoryLocation;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InventoryTestCase;

class StockTransferWorkflowTest extends InventoryTestCase
{
    public function test_dispatch_uses_transit_and_distinct_receiver_confirms(): void
    {
        $source = $this->location('ORDER-SRC');
        $destination = $this->location('ORDER-DST');
        $this->receipt($source, 2, 'order-receipt', 1000)->assertSuccessful();

        $transfer = $this->postJson('/api/v1/inventory/transfer-orders', [
            'source_location_id' => $source->public_id,
            'destination_location_id' => $destination->public_id,
            'lines' => [[
                'product_id' => $this->product->public_id,
                'packaging_id' => $this->packaging->public_id,
                'quantity' => 1,
            ]],
        ])->assertSuccessful()->json('data.id');

        $this->withHeader('Idempotency-Key', 'order-dispatch-key')
            ->postJson("/api/v1/inventory/transfer-orders/{$transfer}/dispatch")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'in_transit');

        $transit = InventoryLocation::query()
            ->where('code', '__TRANSIT__')
            ->firstOrFail();

        $this->assertDatabaseHas('inventory_balances', [
            'inventory_location_id' => $transit->id,
            'product_id' => $this->product->id,
            'quantity_on_hand_base' => 50,
        ]);

        $this->withHeader('Idempotency-Key', 'order-self-receive')
            ->postJson("/api/v1/inventory/transfer-orders/{$transfer}/receive")
            ->assertConflict()
            ->assertJsonPath('code', 'inventory_transfer_self_receive_forbidden');

        $checker = $this->superadmin('transfer-checker@example.test');
        Sanctum::actingAs($checker);

        $this->withHeader('Idempotency-Key', 'order-receive-key')
            ->postJson("/api/v1/inventory/transfer-orders/{$transfer}/receive")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'received');

        $destinationBalance = InventoryBalance::query()
            ->where('inventory_location_id', $destination->id)
            ->where('product_id', $this->product->id)
            ->firstOrFail();

        $this->assertSame('50.000000', $destinationBalance->quantity_on_hand_base);
        $this->assertSame('50000.000000', $destinationBalance->inventory_value);
    }

    public function test_in_transit_cancel_returns_stock_to_source(): void
    {
        $source = $this->location('CANCEL-SRC');
        $destination = $this->location('CANCEL-DST');
        $this->receipt($source, 1, 'cancel-receipt', 1000)->assertSuccessful();

        $transfer = $this->postJson('/api/v1/inventory/transfer-orders', [
            'source_location_id' => $source->public_id,
            'destination_location_id' => $destination->public_id,
            'lines' => [[
                'product_id' => $this->product->public_id,
                'quantity' => 20,
            ]],
        ])->assertSuccessful()->json('data.id');

        $this->withHeader('Idempotency-Key', 'cancel-dispatch-key')
            ->postJson("/api/v1/inventory/transfer-orders/{$transfer}/dispatch")
            ->assertSuccessful();

        $this->withHeaders([
            'Idempotency-Key' => 'cancel-return-key',
            'X-Privileged-Reason' => 'Destination unavailable; return stock to source',
        ])->postJson("/api/v1/inventory/transfer-orders/{$transfer}/cancel")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'cancelled');

        $balance = InventoryBalance::query()
            ->where('inventory_location_id', $source->id)
            ->where('product_id', $this->product->id)
            ->firstOrFail();

        $this->assertSame('50.000000', $balance->quantity_on_hand_base);
    }
}
