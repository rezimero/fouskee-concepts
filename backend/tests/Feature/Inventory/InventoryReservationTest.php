<?php

namespace Tests\Feature\Inventory;

use Tests\Support\InventoryTestCase;

class InventoryReservationTest extends InventoryTestCase
{
    public function test_reservation_reduces_available_not_on_hand(): void
    {
        $location = $this->location('RES');
        $this->receipt($location, 2)->assertSuccessful();

        $response = $this->postJson('/api/v1/inventory/reservations', [
            'location_id' => $location->public_id,
            'product_id' => $this->product->public_id,
            'quantity_base' => 30,
        ])->assertCreated();

        $this->getJson('/api/v1/inventory/balances')
            ->assertOk()
            ->assertJsonPath('data.0.on_hand_base', '100.000000')
            ->assertJsonPath('data.0.reserved_base', '30.000000')
            ->assertJsonPath('data.0.available_base', '70.000000');

        $reservationId = $response->json('data.id');

        $this->postJson(
            "/api/v1/inventory/reservations/{$reservationId}/release",
        )->assertOk()->assertJsonPath('data.status', 'released');
    }

    public function test_reservation_cannot_exceed_available_stock(): void
    {
        $location = $this->location('RES2');
        $this->receipt($location, 1)->assertSuccessful();

        $this->postJson('/api/v1/inventory/reservations', [
            'location_id' => $location->public_id,
            'product_id' => $this->product->public_id,
            'quantity_base' => 60,
        ])
            ->assertConflict()
            ->assertJsonPath('code', 'inventory_insufficient_available_stock');
    }
}
