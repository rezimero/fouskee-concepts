<?php

namespace Tests\Feature\Procurement;

use App\Models\GoodsReceipt;
use App\Models\InventoryBalance;
use App\Models\PurchaseOrder;
use Tests\Support\ProcurementTestCase;

class GoodsReceiptReversalTest extends ProcurementTestCase
{
    public function test_receipt_correction_uses_owned_reversal_workflow(): void
    {
        $location = $this->location('PROC-REV');
        $orderId = $this->approvedOrder('reversal-po-key');
        $order = PurchaseOrder::query()
            ->with('lines')
            ->where('public_id', $orderId)
            ->firstOrFail();
        $line = $order->lines->first();

        $receiptId = $this->withHeader(
            'Idempotency-Key',
            'receipt-to-reverse-key',
        )->postJson('/api/v1/procurement/goods-receipts', [
            'purchase_order_id' => $orderId,
            'location_id' => $location->public_id,
            'lines' => [[
                'purchase_order_line_id' => $line->public_id,
                'quantity' => 1,
            ]],
        ])->assertSuccessful()->json('data.id');

        $receipt = GoodsReceipt::query()
            ->with('movement')
            ->where('public_id', $receiptId)
            ->firstOrFail();

        $this->withHeaders([
            'Idempotency-Key' => 'raw-reversal-forbidden-key',
            'X-Privileged-Reason' => 'Attempted raw reversal to verify document ownership',
        ])->postJson(
            "/api/v1/inventory/movements/{$receipt->movement->public_id}/reversals",
        )->assertConflict()
            ->assertJsonPath(
                'code',
                'inventory_document_movement_reversal_forbidden',
            );

        $this->withHeaders([
            'Idempotency-Key' => 'owned-reversal-key',
            'X-Privileged-Reason' => 'Physical receipt entry was verified as erroneous',
        ])->postJson(
            "/api/v1/procurement/goods-receipts/{$receiptId}/reverse",
        )->assertSuccessful()
            ->assertJsonPath('data.status', 'reversed');

        $balance = InventoryBalance::query()
            ->where('inventory_location_id', $location->id)
            ->where('product_id', $this->product->id)
            ->firstOrFail();

        $this->assertSame('0.000000', $balance->quantity_on_hand_base);
        $this->assertSame('approved', $order->refresh()->status->value);
    }
}
