<?php

namespace Tests\Feature\Sales;

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use Tests\Support\SalesTestCase;

class SalesFulfillmentWorkflowTest extends SalesTestCase
{
    public function test_pick_and_dispatch_consume_reserved_stock_once(): void
    {
        [$order, $reservation] = $this->reservedOrder('FUL-DISPATCH');

        $fulfillment = $this->pick($order, [$reservation->public_id], 'pick-001')
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'picked')
            ->json('data.id');

        $this->withHeader('Idempotency-Key', 'dispatch-001')
            ->postJson("/api/v1/sales/fulfillments/{$fulfillment}/dispatch")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'dispatched');

        $this->assertSame(
            '0.000000',
            InventoryBalance::query()->firstOrFail()->quantity_on_hand_base,
        );
        $this->assertDatabaseHas('inventory_reservations', [
            'id' => $reservation->id,
            'status' => 'fulfilled',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'movement_type' => 'sale_issue',
            'source_reference_type' => 'sales.fulfillment',
        ]);
        $this->assertDatabaseHas('sales_orders', [
            'public_id' => $order,
            'status' => 'approved',
        ]);

        $this->withHeader('Idempotency-Key', 'dispatch-001')
            ->postJson("/api/v1/sales/fulfillments/{$fulfillment}/dispatch")
            ->assertSuccessful();

        $this->assertSame(2, InventoryMovement::query()->count());
    }

    public function test_pick_requires_exact_active_reservation_set(): void
    {
        [$order, $reservation] = $this->reservedOrder('FUL-SET');

        $this->pick($order, [], 'pick-empty')
            ->assertUnprocessable();

        $this->pick($order, [$reservation->public_id, str_repeat('A', 26)], 'pick-extra')
            ->assertConflict()
            ->assertJsonPath('code', 'sales_fulfillment_pick_set_mismatch');
    }

    public function test_picked_order_reservations_cannot_be_released(): void
    {
        [$order, $reservation] = $this->reservedOrder('FUL-LOCK');
        $this->pick($order, [$reservation->public_id], 'pick-lock')
            ->assertSuccessful();

        $this->withHeader(
            'X-Privileged-Reason',
            'Attempted release after warehouse pick',
        )->postJson("/api/v1/sales/orders/{$order}/reservation/release")
            ->assertConflict()
            ->assertJsonPath('code', 'sales_order_pick_in_progress');
    }

    public function test_dispatch_reversal_restores_stock_and_new_reservation(): void
    {
        [$order, $reservation] = $this->reservedOrder('FUL-REV');
        $fulfillment = $this->pick(
            $order,
            [$reservation->public_id],
            'pick-rev',
        )->assertSuccessful()->json('data.id');

        $this->withHeader('Idempotency-Key', 'dispatch-rev')
            ->postJson("/api/v1/sales/fulfillments/{$fulfillment}/dispatch")
            ->assertSuccessful();

        $movement = InventoryMovement::query()
            ->where('movement_type', 'sale_issue')
            ->firstOrFail();

        $this->withHeaders([
            'Idempotency-Key' => 'generic-reversal-attempt',
            'X-Privileged-Reason' => 'Trying generic reversal route',
        ])->postJson(
            "/api/v1/inventory/movements/{$movement->public_id}/reversals",
        )
            ->assertConflict()
            ->assertJsonPath('code', 'inventory_document_movement_reversal_forbidden');

        $this->withHeaders([
            'Idempotency-Key' => 'fulfillment-reversal-001',
            'X-Privileged-Reason' => 'Dispatch correction after warehouse error',
        ])->postJson("/api/v1/sales/fulfillments/{$fulfillment}/reverse")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'reversed');

        $this->assertSame(
            '50.000000',
            InventoryBalance::query()->firstOrFail()->quantity_on_hand_base,
        );
        $this->assertSame(
            1,
            InventoryReservation::query()->where('status', 'active')->count(),
        );
        $this->assertDatabaseHas('sales_orders', [
            'public_id' => $order,
            'status' => 'approved',
        ]);
    }

    private function reservedOrder(string $suffix): array
    {
        $location = $this->location($suffix);
        $this->receipt($location, 1, strtolower($suffix).'-stock')
            ->assertSuccessful();

        $order = $this->salesOrder(strtolower($suffix).'-order')
            ->assertSuccessful()
            ->json('data.id');

        $this->postJson("/api/v1/sales/orders/{$order}/submit")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'approved');

        $this->postJson("/api/v1/sales/orders/{$order}/reservation", [
            'location_id' => $location->public_id,
        ])->assertSuccessful();

        return [$order, InventoryReservation::query()->firstOrFail()];
    }

    private function pick(string $order, array $ids, string $key)
    {
        return $this->withHeader('Idempotency-Key', $key)
            ->postJson("/api/v1/sales/orders/{$order}/fulfillments/pick", [
                'reservation_ids' => $ids,
            ]);
    }
}
