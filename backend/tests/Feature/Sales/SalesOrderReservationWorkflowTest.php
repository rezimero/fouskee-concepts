<?php

namespace Tests\Feature\Sales;

use App\Models\InventoryReservation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\SalesTestCase;

class SalesOrderReservationWorkflowTest extends SalesTestCase
{
    public function test_approved_order_reserves_and_releases_inventory(): void
    {
        $location = $this->location('SALES-RES');
        $this->receipt($location, 2, 'sales-res-stock')->assertSuccessful();
        $order = $this->approvedOrder('sales-res-order-001');

        $this->postJson("/api/v1/sales/orders/{$order}/reservation", [
            'location_id' => $location->public_id,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.total_reserved_base', '50.000000');

        $reservation = InventoryReservation::query()->firstOrFail();

        $this->postJson(
            "/api/v1/inventory/reservations/{$reservation->public_id}/release",
        )
            ->assertConflict()
            ->assertJsonPath(
                'code',
                'inventory_owned_reservation_operation_forbidden',
            );

        $this->withHeader(
            'X-Privileged-Reason',
            'Customer changed fulfillment timing',
        )->postJson("/api/v1/sales/orders/{$order}/reservation/release")
            ->assertOk()
            ->assertJsonPath('data.status', 'none');
    }

    public function test_order_cannot_reserve_before_approval(): void
    {
        $location = $this->location('SALES-PRE');
        $this->receipt($location, 1, 'sales-pre-stock')->assertSuccessful();
        $order = $this->salesOrder('sales-pre-order')
            ->assertSuccessful()
            ->json('data.id');

        $this->postJson("/api/v1/sales/orders/{$order}/reservation", [
            'location_id' => $location->public_id,
        ])
            ->assertConflict()
            ->assertJsonPath('code', 'sales_order_not_approved_for_reservation');
    }

    public function test_reservation_spans_lot_and_untracked_stock(): void
    {
        $location = $this->location('SALES-LOT');
        $this->receipt($location, 1, 'sales-untracked-stock')->assertSuccessful();
        $this->receipt(
            $location,
            1,
            'sales-lot-stock',
            1000,
            ['lot_code' => 'LOT-SALES-001'],
        )->assertSuccessful();
        $order = $this->approvedOrder('sales-lot-order', 2);

        $this->postJson("/api/v1/sales/orders/{$order}/reservation", [
            'location_id' => $location->public_id,
        ])->assertOk()->assertJsonPath('data.reservation_count', 2);

        $rows = InventoryReservation::query()
            ->where('source_reference_public_id', $order)
            ->get();

        $this->assertCount(2, $rows);
        $this->assertEquals(100.0, (float) $rows->sum('quantity_base'));
        $this->assertTrue(
            $rows->contains(fn ($row) => $row->inventory_lot_id !== null),
        );
        $this->assertTrue(
            $rows->contains(fn ($row) => $row->inventory_lot_id === null),
        );
    }

    public function test_insufficient_stock_rolls_back_reservations(): void
    {
        $location = $this->location('SALES-SHORT');
        $this->receipt($location, 1, 'sales-short-stock')->assertSuccessful();
        $order = $this->approvedOrder('sales-short-order', 2);

        $this->postJson("/api/v1/sales/orders/{$order}/reservation", [
            'location_id' => $location->public_id,
        ])
            ->assertConflict()
            ->assertJsonPath('code', 'sales_order_insufficient_fulfillment_stock');

        $this->assertDatabaseCount('inventory_reservations', 0);
    }

    public function test_reservation_is_idempotent_and_db_immutable(): void
    {
        $location = $this->location('SALES-IDEM');
        $this->receipt($location, 1, 'sales-idem-stock')->assertSuccessful();
        $order = $this->approvedOrder('sales-idem-order');
        $payload = ['location_id' => $location->public_id];

        $this->postJson(
            "/api/v1/sales/orders/{$order}/reservation",
            $payload,
        )->assertOk();

        $this->postJson(
            "/api/v1/sales/orders/{$order}/reservation",
            $payload,
        )->assertOk()->assertJsonPath('data.reservation_count', 1);

        $this->assertDatabaseCount('inventory_reservations', 1);
        $reservation = InventoryReservation::query()->firstOrFail();

        $this->expectException(QueryException::class);

        DB::table('inventory_reservations')
            ->whereKey($reservation->id)
            ->update(['quantity_base' => 1]);
    }

    private function approvedOrder(
        string $key,
        int|float $quantity = 1,
    ): string {
        $order = $this->salesOrder(
            $key,
            'prepaid',
            null,
            $quantity,
        )->assertSuccessful()->json('data.id');

        $this->postJson("/api/v1/sales/orders/{$order}/submit")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'approved');

        return $order;
    }
}
