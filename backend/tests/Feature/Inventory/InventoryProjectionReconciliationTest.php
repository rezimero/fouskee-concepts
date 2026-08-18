<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryBalance;
use Tests\Support\InventoryTestCase;

class InventoryProjectionReconciliationTest extends InventoryTestCase
{
    public function test_projection_can_be_detected_and_rebuilt_from_ledger(): void
    {
        $location = $this->location('RECON');
        $this->receipt($location, 1, 'recon-receipt', 1000)->assertSuccessful();

        InventoryBalance::query()
            ->where('inventory_location_id', $location->id)
            ->update([
                'quantity_on_hand_base' => 999,
                'inventory_value' => 999,
            ]);

        $this->artisan('inventory:reconcile-projections', [
            '--organization' => $this->organization->code,
        ])->assertFailed();

        $this->artisan('inventory:reconcile-projections', [
            '--organization' => $this->organization->code,
            '--rebuild' => true,
        ])->assertSuccessful();

        $balance = InventoryBalance::query()
            ->where('inventory_location_id', $location->id)
            ->firstOrFail();

        $this->assertSame('50.000000', $balance->quantity_on_hand_base);
        $this->assertSame('50000.000000', $balance->inventory_value);
        $this->assertSame('1000.000000', $balance->weighted_average_unit_cost);
    }
}
