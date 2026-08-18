<?php

namespace Tests\Feature\Inventory;

use Tests\Support\InventoryTestCase;

class InventoryAdjustmentTest extends InventoryTestCase
{
    public function test_adjustment_requires_privileged_reason(): void
    {
        $location = $this->location('ADJ');

        $this->withHeader('Idempotency-Key', 'adjustment-key-0001')
            ->postJson('/api/v1/inventory/adjustments', [
                'location_id' => $location->public_id,
                'lines' => [[
                    'product_id' => $this->product->public_id,
                    'quantity_delta' => 10,
                    'unit_cost_base' => 1000,
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'privileged_reason_required');
    }

    public function test_controlled_adjustment_posts_with_reason(): void
    {
        $location = $this->location('ADJ2');

        $this->withHeaders([
            'Idempotency-Key' => 'adjustment-key-0002',
            'X-Privileged-Reason' => 'Opening physical stock verification',
        ])->postJson('/api/v1/inventory/adjustments', [
            'location_id' => $location->public_id,
            'lines' => [[
                'product_id' => $this->product->public_id,
                'quantity_delta' => 10,
                'unit_cost_base' => 1000,
            ]],
        ])
            ->assertSuccessful()
            ->assertJsonPath('data.type', 'adjustment')
            ->assertJsonPath(
                'data.business_reason',
                'Opening physical stock verification',
            );
    }
}
