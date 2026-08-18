<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use Tests\Support\InventoryTestCase;

class InventoryReversalTest extends InventoryTestCase
{
    public function test_reversal_negates_original_movement(): void
    {
        $location = $this->location('REV');
        $this->receipt($location, 1)->assertSuccessful();

        $original = InventoryMovement::query()->firstOrFail();

        $this->withHeaders([
            'Idempotency-Key' => 'reversal-key-0001',
            'X-Privileged-Reason' => 'Correcting validation receipt',
        ])->postJson(
            "/api/v1/inventory/movements/{$original->public_id}/reversals",
        )
            ->assertSuccessful()
            ->assertJsonPath('data.type', 'reversal');

        $this->assertSame(
            '0.000000',
            InventoryBalance::query()->firstOrFail()->quantity_on_hand_base,
        );

        $this->assertSame(2, InventoryMovement::query()->count());
    }

    public function test_same_movement_cannot_be_reversed_twice(): void
    {
        $location = $this->location('REV2');
        $this->receipt($location, 1)->assertSuccessful();
        $original = InventoryMovement::query()->firstOrFail();

        $this->withHeaders([
            'Idempotency-Key' => 'reversal-first-key',
            'X-Privileged-Reason' => 'First controlled reversal',
        ])->postJson(
            "/api/v1/inventory/movements/{$original->public_id}/reversals",
        )->assertSuccessful();

        $this->withHeaders([
            'Idempotency-Key' => 'reversal-second-key',
            'X-Privileged-Reason' => 'Second reversal attempt',
        ])->postJson(
            "/api/v1/inventory/movements/{$original->public_id}/reversals",
        )
            ->assertConflict()
            ->assertJsonPath('code', 'inventory_movement_already_reversed');
    }

    public function test_reversal_retry_with_same_key_is_idempotent(): void
    {
        $location = $this->location('REV-IDEMP');
        $this->receipt($location, 1)->assertSuccessful();
        $original = InventoryMovement::query()->firstOrFail();

        $headers = [
            'Idempotency-Key' => 'reversal-idempotent-key',
            'X-Privileged-Reason' => 'Controlled retry-safe reversal',
        ];

        $endpoint = "/api/v1/inventory/movements/{$original->public_id}/reversals";

        $this->withHeaders($headers)->postJson($endpoint)->assertSuccessful();
        $this->withHeaders($headers)->postJson($endpoint)->assertSuccessful();

        $this->assertSame(2, InventoryMovement::query()->count());
    }
}
