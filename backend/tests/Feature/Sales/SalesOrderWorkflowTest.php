<?php

namespace Tests\Feature\Sales;

use App\Models\SalesOrderLine;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use LogicException;
use Tests\Support\SalesTestCase;

class SalesOrderWorkflowTest extends SalesTestCase
{
    public function test_server_prices_and_snapshots_order_line(): void
    {
        $response = $this->salesOrder('sales-price-001')
            ->assertSuccessful();

        $response
            ->assertJsonPath('data.lines.0.list_unit_price', '62000.000000')
            ->assertJsonPath('data.lines.0.floor_unit_price', '59000.000000')
            ->assertJsonPath('data.lines.0.effective_unit_price', '62000.000000')
            ->assertJsonPath('data.lines.0.quantity_base', '50.000000')
            ->assertJsonPath('data.lines.0.price_exception', false);
    }

    public function test_price_below_floor_requires_independent_approval(): void
    {
        $order = $this->salesOrder('sales-floor-001', 'prepaid', 58000)
            ->assertSuccessful()
            ->json('data.id');

        $this->postJson("/api/v1/sales/orders/{$order}/submit")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.exception_codes.0', 'price_below_floor');

        $this->withHeader(
            'X-Privileged-Reason',
            'Attempted self approval for control validation',
        )->postJson("/api/v1/sales/orders/{$order}/approve")
            ->assertConflict()
            ->assertJsonPath('code', 'approval_self_decision_forbidden');

        $checker = $this->superadmin(
            'sales-checker-'.Str::lower(Str::random(8)).'@example.test',
        );
        Sanctum::actingAs($checker);

        $this->withHeader(
            'X-Privileged-Reason',
            'Independent below-floor authorization',
        )->postJson("/api/v1/sales/orders/{$order}/approve")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_credit_overage_requires_approval(): void
    {
        $order = $this->salesOrder(
            'sales-credit-001',
            'credit',
            null,
            10,
        )->assertSuccessful()->json('data.id');

        $this->postJson("/api/v1/sales/orders/{$order}/submit")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonFragment(['customer_credit_limit_exceeded']);
    }

    public function test_sales_order_creation_is_idempotent(): void
    {
        $first = $this->salesOrder('sales-idempotent-001')
            ->assertSuccessful()
            ->json('data.id');

        $second = $this->salesOrder('sales-idempotent-001')
            ->assertSuccessful()
            ->json('data.id');

        $this->assertSame($first, $second);
        $this->assertDatabaseCount('sales_orders', 1);
    }

    public function test_submitted_sales_line_cannot_be_silently_edited(): void
    {
        $order = $this->salesOrder('sales-freeze-001')
            ->assertSuccessful()
            ->json('data.id');

        $this->postJson("/api/v1/sales/orders/{$order}/submit")
            ->assertSuccessful();

        $line = SalesOrderLine::query()->firstOrFail();

        $this->expectException(LogicException::class);
        $line->update(['effective_unit_price' => 1]);
    }
}
