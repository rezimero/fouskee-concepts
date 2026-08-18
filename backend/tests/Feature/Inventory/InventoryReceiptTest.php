<?php

namespace Tests\Feature\Inventory;

use App\Models\AuditEvent;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use Tests\Support\InventoryTestCase;

class InventoryReceiptTest extends InventoryTestCase
{
    public function test_receipt_posts_base_quantity_and_audit_event(): void
    {
        $location = $this->location('MAIN');

        $this->receipt($location, 2)
            ->assertSuccessful()
            ->assertJsonPath('data.type', 'receipt')
            ->assertJsonPath('data.lines.0.quantity_base', '100.000000');

        $this->assertDatabaseHas('inventory_balances', [
            'organization_id' => $this->organization->id,
            'inventory_location_id' => $location->id,
            'product_id' => $this->product->id,
            'quantity_on_hand_base' => 100,
        ]);

        $this->assertTrue(
            AuditEvent::query()->where('event', 'inventory.movement.posted')->exists(),
        );
    }

    public function test_idempotent_retry_does_not_post_twice(): void
    {
        $location = $this->location('IDEMP');

        $this->receipt($location, 1, 'same-receipt-key')->assertSuccessful();
        $this->receipt($location, 1, 'same-receipt-key')->assertSuccessful();

        $this->assertSame(1, InventoryMovement::query()->count());
        $this->assertSame(
            '50.000000',
            InventoryBalance::query()->firstOrFail()->quantity_on_hand_base,
        );
    }

    public function test_reused_idempotency_key_with_new_payload_is_rejected(): void
    {
        $location = $this->location('CONFLICT');

        $this->receipt($location, 1, 'conflict-key')->assertSuccessful();

        $this->receipt($location, 2, 'conflict-key')
            ->assertConflict()
            ->assertJsonPath('code', 'inventory_idempotency_conflict');
    }
}
