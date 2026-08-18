<?php

namespace Tests\Feature\Procurement;

use Tests\Support\ProcurementTestCase;

class SupplierInvoiceTest extends ProcurementTestCase
{
    public function test_supplier_invoice_number_is_duplicate_hardened(): void
    {
        $this->invoice('INV-001', 'invoice-key-001')
            ->assertSuccessful();

        $this->invoice('INV 001', 'invoice-key-002')
            ->assertConflict()
            ->assertJsonPath(
                'code',
                'procurement_duplicate_supplier_invoice',
            );

        $this->assertDatabaseCount('supplier_invoices', 1);
    }

    private function invoice(string $number, string $key)
    {
        return $this->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/procurement/supplier-invoices', [
                'supplier_id' => $this->supplier->public_id,
                'invoice_number' => $number,
                'invoice_date' => now()->toDateString(),
                'lines' => [[
                    'product_id' => $this->product->public_id,
                    'packaging_id' => $this->packaging->public_id,
                    'quantity' => 1,
                    'unit_price' => 50000,
                ]],
            ]);
    }
}
