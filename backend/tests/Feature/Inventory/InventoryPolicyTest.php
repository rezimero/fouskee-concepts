<?php

namespace Tests\Feature\Inventory;

use Tests\Support\InventoryTestCase;

class InventoryPolicyTest extends InventoryTestCase
{
    public function test_policy_update_requires_reason_and_can_disable_immediate_transfer(): void
    {
        $this->putJson('/api/v1/inventory/policy', [
            'count_variance_approval_value_threshold' => 250000,
            'backdate_window_days' => 3,
            'require_separate_transfer_receiver' => true,
            'allow_immediate_transfers' => false,
        ])->assertUnprocessable()
            ->assertJsonPath('code', 'privileged_reason_required');

        $this->withHeader(
            'X-Privileged-Reason',
            'Tightening transfer controls before operational rollout',
        )->putJson('/api/v1/inventory/policy', [
            'count_variance_approval_value_threshold' => 250000,
            'backdate_window_days' => 3,
            'require_separate_transfer_receiver' => true,
            'allow_immediate_transfers' => false,
        ])->assertOk()
            ->assertJsonPath('data.allow_immediate_transfers', false);

        $source = $this->location('POL-SRC');
        $destination = $this->location('POL-DST');
        $this->receipt($source, 1, 'policy-receipt', 1000)->assertSuccessful();

        $this->withHeader('Idempotency-Key', 'policy-direct-transfer')
            ->postJson('/api/v1/inventory/transfers', [
                'source_location_id' => $source->public_id,
                'destination_location_id' => $destination->public_id,
                'lines' => [[
                    'product_id' => $this->product->public_id,
                    'quantity' => 10,
                ]],
            ])->assertConflict()
            ->assertJsonPath('code', 'inventory_immediate_transfer_disabled');
    }
}
