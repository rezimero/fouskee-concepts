<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryMovement;
use Tests\Support\InventoryTestCase;

class OperationalMovementOwnershipTest extends InventoryTestCase
{
    public function test_stock_count_movement_uses_owning_document_workflow(): void
    {
        $location = $this->location('OWN-COUNT');
        $this->receipt($location, 1, 'own-count-receipt', 1000)->assertSuccessful();

        $count = $this->postJson('/api/v1/inventory/counts', [
            'location_id' => $location->public_id,
            'reason_code' => 'cycle_count',
            'lines' => [[
                'product_id' => $this->product->public_id,
                'counted_quantity_base' => 45,
            ]],
        ])->assertSuccessful()->json('data.id');

        $this->withHeaders([
            'Idempotency-Key' => 'own-count-submit',
            'X-Privileged-Reason' => 'Posting controlled count for ownership test',
        ])->postJson("/api/v1/inventory/counts/{$count}/submit")
            ->assertSuccessful();

        $movement = InventoryMovement::query()
            ->where('source_reference_type', 'stock_count')
            ->firstOrFail();

        $this->withHeaders([
            'Idempotency-Key' => 'own-count-reverse',
            'X-Privileged-Reason' => 'Attempted generic reversal of document movement',
        ])->postJson("/api/v1/inventory/movements/{$movement->public_id}/reversals")
            ->assertConflict()
            ->assertJsonPath('code', 'inventory_document_movement_reversal_forbidden');
    }
}
