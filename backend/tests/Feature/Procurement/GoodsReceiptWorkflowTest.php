<?php

namespace Tests\Feature\Procurement;

use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\PurchaseOrder;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\ProcurementTestCase;

class GoodsReceiptWorkflowTest extends ProcurementTestCase
{
    public function test_partial_receipts_post_inventory_and_update_order_status(): void
    {
        $location = $this->location('PROC-REC');
        $orderId = $this->approvedOrder('partial-po-key');
        $order = PurchaseOrder::query()
            ->with('lines')
            ->where('public_id', $orderId)
            ->firstOrFail();
        $line = $order->lines->first();

        $this->receiptAgainst(
            $orderId,
            $line->public_id,
            $location->public_id,
            0.5,
            'partial-receipt-1',
        )->assertSuccessful()
            ->assertJsonPath('data.status', 'posted');

        $this->assertSame(
            'partially_received',
            $order->refresh()->status->value,
        );

        $this->receiptAgainst(
            $orderId,
            $line->public_id,
            $location->public_id,
            0.5,
            'partial-receipt-2',
        )->assertSuccessful();

        $this->assertSame('received', $order->refresh()->status->value);

        $balance = InventoryBalance::query()
            ->where('inventory_location_id', $location->id)
            ->where('product_id', $this->product->id)
            ->firstOrFail();

        $this->assertSame('50.000000', $balance->quantity_on_hand_base);
        $this->assertSame('50000.000000', $balance->inventory_value);

        $movement = InventoryMovement::query()->latest('id')->firstOrFail();
        $this->assertSame(
            'procurement.goods_receipt',
            $movement->source_reference_type,
        );
    }

    public function test_over_receipt_requires_independent_exception_approval(): void
    {
        $location = $this->location('PROC-OVER');
        $orderId = $this->approvedOrder('over-po-key');
        $line = PurchaseOrder::query()
            ->with('lines')
            ->where('public_id', $orderId)
            ->firstOrFail()
            ->lines
            ->first();

        $receipt = $this->receiptAgainst(
            $orderId,
            $line->public_id,
            $location->public_id,
            1.1,
            'over-receipt-key',
        )->assertSuccessful()
            ->assertJsonPath('data.status', 'pending_exception')
            ->json('data.id');

        $this->assertDatabaseCount('inventory_movements', 0);

        $this->withHeader(
            'X-Privileged-Reason',
            'Attempted self approval of receiving exception',
        )->postJson(
            "/api/v1/procurement/goods-receipts/{$receipt}/approve-exception",
        )->assertConflict()
            ->assertJsonPath('code', 'approval_self_decision_forbidden');

        $checker = $this->superadmin(
            'receipt-checker-'.Str::lower(Str::random(8)).'@example.test',
        );
        Sanctum::actingAs($checker);

        $this->withHeader(
            'X-Privileged-Reason',
            'Independent approval of verified supplier over-delivery',
        )->postJson(
            "/api/v1/procurement/goods-receipts/{$receipt}/approve-exception",
        )->assertSuccessful()
            ->assertJsonPath('data.status', 'posted');

        $balance = InventoryBalance::query()
            ->where('inventory_location_id', $location->id)
            ->where('product_id', $this->product->id)
            ->firstOrFail();

        $this->assertSame('55.000000', $balance->quantity_on_hand_base);
    }

    private function receiptAgainst(
        string $orderId,
        string $lineId,
        string $locationId,
        float $quantity,
        string $key,
    ) {
        return $this->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/procurement/goods-receipts', [
                'purchase_order_id' => $orderId,
                'location_id' => $locationId,
                'lines' => [[
                    'purchase_order_line_id' => $lineId,
                    'quantity' => $quantity,
                ]],
            ]);
    }
}
