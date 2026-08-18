<?php

namespace Tests\Feature\Inventory;

use Tests\Support\InventoryTestCase;

class InventoryBackdatePolicyTest extends InventoryTestCase
{
    public function test_material_backdate_requires_privileged_reason(): void
    {
        $location = $this->location('BACKDATE');
        $occurredAt = now()->subDays(10)->toISOString();

        $this->withHeader('Idempotency-Key', 'backdate-no-reason')
            ->postJson('/api/v1/inventory/receipts', [
                'location_id' => $location->public_id,
                'occurred_at' => $occurredAt,
                'lines' => [[
                    'product_id' => $this->product->public_id,
                    'quantity' => 10,
                    'unit_cost_base' => 1000,
                ]],
            ])->assertUnprocessable()
            ->assertJsonPath('code', 'inventory_backdate_reason_required');

        $this->withHeaders([
            'Idempotency-Key' => 'backdate-with-reason',
            'X-Privileged-Reason' => 'Late warehouse document received after physical receipt',
        ])->postJson('/api/v1/inventory/receipts', [
            'location_id' => $location->public_id,
            'occurred_at' => $occurredAt,
            'lines' => [[
                'product_id' => $this->product->public_id,
                'quantity' => 10,
                'unit_cost_base' => 1000,
            ]],
        ])->assertSuccessful()
            ->assertJsonPath('data.type', 'receipt');
    }
}
