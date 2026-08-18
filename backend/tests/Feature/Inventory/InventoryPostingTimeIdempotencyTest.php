<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryMovement;
use Tests\Support\InventoryTestCase;

class InventoryPostingTimeIdempotencyTest extends InventoryTestCase
{
    public function test_server_generated_posting_time_is_not_part_of_idempotency_input(): void
    {
        $location = $this->location('IDEMP-TIME');

        $this->receipt($location, 1, 'implicit-time-key', 1000)
            ->assertSuccessful();

        usleep(1000);

        $this->receipt($location, 1, 'implicit-time-key', 1000)
            ->assertSuccessful();

        $this->assertSame(1, InventoryMovement::query()->count());
    }

    public function test_explicit_business_time_remains_part_of_idempotency_input(): void
    {
        $location = $this->location('IDEMP-EXPLICIT');
        $first = now()->subHour()->startOfMinute()->toISOString();
        $second = now()->subMinutes(30)->startOfMinute()->toISOString();

        $this->withHeader('Idempotency-Key', 'explicit-time-key')
            ->postJson('/api/v1/inventory/receipts', [
                'location_id' => $location->public_id,
                'occurred_at' => $first,
                'lines' => [[
                    'product_id' => $this->product->public_id,
                    'quantity' => 10,
                    'unit_cost_base' => 1000,
                ]],
            ])->assertSuccessful();

        $this->withHeader('Idempotency-Key', 'explicit-time-key')
            ->postJson('/api/v1/inventory/receipts', [
                'location_id' => $location->public_id,
                'occurred_at' => $second,
                'lines' => [[
                    'product_id' => $this->product->public_id,
                    'quantity' => 10,
                    'unit_cost_base' => 1000,
                ]],
            ])->assertConflict()
            ->assertJsonPath('code', 'inventory_idempotency_conflict');
    }
}
