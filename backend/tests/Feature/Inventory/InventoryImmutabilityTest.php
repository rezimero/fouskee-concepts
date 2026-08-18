<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryMovement;
use LogicException;
use Tests\Support\InventoryTestCase;

class InventoryImmutabilityTest extends InventoryTestCase
{
    public function test_posted_movement_cannot_be_updated(): void
    {
        $location = $this->location('IMM');
        $this->receipt($location, 1)->assertSuccessful();

        $movement = InventoryMovement::query()->firstOrFail();

        $this->expectException(LogicException::class);
        $movement->update(['business_reason' => 'silent rewrite']);
    }

    public function test_posted_movement_cannot_be_deleted(): void
    {
        $location = $this->location('IMM2');
        $this->receipt($location, 1)->assertSuccessful();

        $movement = InventoryMovement::query()->firstOrFail();

        $this->expectException(LogicException::class);
        $movement->delete();
    }
}
