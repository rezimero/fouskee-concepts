<?php

namespace Tests\Feature\Procurement;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use LogicException;
use Tests\Support\ProcurementTestCase;

class PurchaseOrderWorkflowTest extends ProcurementTestCase
{
    public function test_purchase_order_creation_is_idempotent(): void
    {
        $first = $this->purchaseOrder('po-idempotent-key')
            ->assertSuccessful()
            ->json('data.id');

        $second = $this->purchaseOrder('po-idempotent-key')
            ->assertSuccessful()
            ->json('data.id');

        $this->assertSame($first, $second);
        $this->assertDatabaseCount('purchase_orders', 1);
    }

    public function test_purchase_order_requires_independent_approval(): void
    {
        $order = $this->purchaseOrder('po-approval-key')
            ->assertSuccessful()
            ->json('data.id');

        $this->postJson(
            "/api/v1/procurement/purchase-orders/{$order}/submit",
        )->assertSuccessful()
            ->assertJsonPath('data.status', 'submitted');

        $this->withHeader(
            'X-Privileged-Reason',
            'Attempted self approval for control validation',
        )->postJson(
            "/api/v1/procurement/purchase-orders/{$order}/approve",
        )->assertConflict()
            ->assertJsonPath('code', 'approval_self_decision_forbidden');

        $checker = $this->superadmin(
            'po-checker-'.Str::lower(Str::random(8)).'@example.test',
        );
        Sanctum::actingAs($checker);

        $this->withHeader(
            'X-Privileged-Reason',
            'Independent purchase-order authorization',
        )->postJson(
            "/api/v1/procurement/purchase-orders/{$order}/approve",
        )->assertSuccessful()
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_submitted_order_line_cannot_be_silently_edited(): void
    {
        $order = $this->purchaseOrder('po-freeze-key')
            ->assertSuccessful()
            ->json('data.id');

        $this->postJson(
            "/api/v1/procurement/purchase-orders/{$order}/submit",
        )->assertSuccessful();

        $line = PurchaseOrderLine::query()->firstOrFail();

        $this->expectException(LogicException::class);
        $line->update(['unit_price' => 1]);
    }

    public function test_order_is_organization_scoped(): void
    {
        $visible = $this->purchaseOrder('po-scope-key')
            ->assertSuccessful()
            ->json('data.id');

        $this->getJson('/api/v1/procurement/purchase-orders')
            ->assertOk()
            ->assertJsonFragment(['id' => $visible]);

        $this->assertSame(
            1,
            PurchaseOrder::query()
                ->where('organization_id', $this->organization->id)
                ->count(),
        );
    }
}
