<?php

namespace Tests\Feature\Procurement;

use App\Models\GoodsReceiptLine;
use App\Models\PurchaseOrder;
use App\Models\SupplierInvoiceLine;
use LogicException;
use Tests\Support\ProcurementTestCase;

class ProcurementEvidenceImmutabilityTest extends ProcurementTestCase
{
    public function test_goods_receipt_line_is_immutable_after_creation(): void
    {
        $location = $this->location('PROC-IMM');
        $orderId = $this->approvedOrder('immutability-po-key');
        $order = PurchaseOrder::query()
            ->with('lines')
            ->where('public_id', $orderId)
            ->firstOrFail();

        $this->withHeader('Idempotency-Key', 'immutability-receipt-key')
            ->postJson('/api/v1/procurement/goods-receipts', [
                'purchase_order_id' => $orderId,
                'location_id' => $location->public_id,
                'lines' => [[
                    'purchase_order_line_id' => $order->lines->first()->public_id,
                    'quantity' => 1,
                ]],
            ])->assertSuccessful();

        $line = GoodsReceiptLine::query()->firstOrFail();

        $this->expectException(LogicException::class);
        $line->update(['received_quantity' => 999]);
    }

    public function test_supplier_invoice_line_is_immutable_after_creation(): void
    {
        $this->withHeader('Idempotency-Key', 'immutability-invoice-key')
            ->postJson('/api/v1/procurement/supplier-invoices', [
                'supplier_id' => $this->supplier->public_id,
                'invoice_number' => 'IMM-INV-001',
                'invoice_date' => now()->toDateString(),
                'lines' => [[
                    'product_id' => $this->product->public_id,
                    'quantity' => 10,
                    'unit_price' => 1000,
                ]],
            ])->assertSuccessful();

        $line = SupplierInvoiceLine::query()->firstOrFail();

        $this->expectException(LogicException::class);
        $line->delete();
    }
}
