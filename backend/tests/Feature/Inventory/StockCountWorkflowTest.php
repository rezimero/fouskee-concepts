<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryPolicy;
use App\Models\StockCountSession;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InventoryTestCase;

class StockCountWorkflowTest extends InventoryTestCase
{
    public function test_small_variance_posts_without_second_approval(): void
    {
        $location = $this->location('COUNT-SMALL');
        $this->receipt($location, 1, 'count-small-receipt', 1000)->assertSuccessful();

        $count = $this->postJson('/api/v1/inventory/counts', [
            'location_id' => $location->public_id,
            'reason_code' => 'cycle_count',
            'lines' => [[
                'product_id' => $this->product->public_id,
                'counted_quantity_base' => 45,
            ]],
        ])->assertSuccessful()->json('data.id');

        $this->withHeaders([
            'Idempotency-Key' => 'count-small-submit',
            'X-Privileged-Reason' => 'Routine cycle count variance verification',
        ])->postJson("/api/v1/inventory/counts/{$count}/submit")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'posted');

        $this->getJson('/api/v1/inventory/balances')
            ->assertJsonFragment(['on_hand_base' => '45.000000']);
    }

    public function test_large_variance_requires_distinct_checker(): void
    {
        $location = $this->location('COUNT-LARGE');
        $this->receipt($location, 1, 'count-large-receipt', 1000)->assertSuccessful();

        InventoryPolicy::query()->updateOrCreate(
            ['organization_id' => $this->organization->id],
            [
                'count_variance_approval_value_threshold' => 1000,
                'backdate_window_days' => 2,
                'require_separate_transfer_receiver' => true,
                'allow_immediate_transfers' => true,
            ],
        );

        $count = $this->postJson('/api/v1/inventory/counts', [
            'location_id' => $location->public_id,
            'reason_code' => 'investigation',
            'lines' => [[
                'product_id' => $this->product->public_id,
                'counted_quantity_base' => 0,
            ]],
        ])->assertSuccessful()->json('data.id');

        $headers = [
            'Idempotency-Key' => 'count-large-submit',
            'X-Privileged-Reason' => 'Material stock variance requires approval',
        ];

        $this->withHeaders($headers)
            ->postJson("/api/v1/inventory/counts/{$count}/submit")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'pending_approval');

        $this->withHeaders([
            'Idempotency-Key' => 'count-large-approve-self',
            'X-Privileged-Reason' => 'Attempted self approval for validation',
        ])->postJson("/api/v1/inventory/counts/{$count}/approve")
            ->assertConflict()
            ->assertJsonPath('code', 'approval_self_decision_forbidden');

        $checker = $this->superadmin('inventory-checker@example.test');
        Sanctum::actingAs($checker);

        $this->withHeaders([
            'Idempotency-Key' => 'count-large-approve-checker',
            'X-Privileged-Reason' => 'Independent approval of investigated variance',
        ])->postJson("/api/v1/inventory/counts/{$count}/approve")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'posted');
    }

    public function test_count_becomes_stale_when_stock_changes(): void
    {
        $location = $this->location('COUNT-STALE');
        $this->receipt($location, 1, 'count-stale-receipt', 1000)->assertSuccessful();

        $count = $this->postJson('/api/v1/inventory/counts', [
            'location_id' => $location->public_id,
            'reason_code' => 'cycle_count',
            'lines' => [[
                'product_id' => $this->product->public_id,
                'counted_quantity_base' => 50,
            ]],
        ])->assertSuccessful()->json('data.id');

        $this->receipt($location, 1, 'count-after-snapshot', 1000)->assertSuccessful();

        $this->withHeaders([
            'Idempotency-Key' => 'count-stale-submit',
            'X-Privileged-Reason' => 'Submitting stale count for validation',
        ])->postJson("/api/v1/inventory/counts/{$count}/submit")
            ->assertConflict()
            ->assertJsonPath('code', 'inventory_count_stale');

        $this->assertSame(
            'draft',
            StockCountSession::query()
                ->where('public_id', $count)
                ->firstOrFail()
                ->status
                ->value,
        );
    }
}
