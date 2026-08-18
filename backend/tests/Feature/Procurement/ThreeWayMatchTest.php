<?php

namespace Tests\Feature\Procurement;

use App\Models\PurchaseOrder;
use App\Models\SupplierInvoice;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\ProcurementTestCase;

class ThreeWayMatchTest extends ProcurementTestCase
{
    public function test_exact_three_way_match_becomes_payment_eligible(): void
    {
        [$order, $line, $location] = $this->receivedOrder('MATCH-EXACT');

        $invoice = $this->linkedInvoice(
            $line->public_id,
            1,
            50000,
            'MATCH-INV-001',
            'match-invoice-key-1',
        )->assertSuccessful()->json('data.id');

        $this->withHeader('Idempotency-Key', 'match-run-key-1')
            ->postJson(
                "/api/v1/procurement/supplier-invoices/{$invoice}/match",
            )->assertSuccessful()
            ->assertJsonPath('data.result', 'matched');

        $this->getJson(
            "/api/v1/procurement/supplier-invoices/{$invoice}",
        )->assertOk()
            ->assertJsonPath('data.status', 'matched')
            ->assertJsonPath('data.eligible_for_payment', true);
    }

    public function test_price_exception_requires_independent_approval(): void
    {
        [, $line] = $this->receivedOrder('MATCH-PRICE');

        $invoice = $this->linkedInvoice(
            $line->public_id,
            1,
            52000,
            'MATCH-INV-002',
            'match-invoice-key-2',
        )->assertSuccessful()->json('data.id');

        $run = $this->withHeader('Idempotency-Key', 'match-run-key-2')
            ->postJson(
                "/api/v1/procurement/supplier-invoices/{$invoice}/match",
            )->assertSuccessful()
            ->assertJsonPath('data.result', 'exception')
            ->json('data.id');

        $this->withHeader(
            'X-Privileged-Reason',
            'Attempted self approval of supplier price exception',
        )->postJson(
            "/api/v1/procurement/match-runs/{$run}/approve-exception",
        )->assertConflict()
            ->assertJsonPath('code', 'approval_self_decision_forbidden');

        $checker = $this->superadmin(
            'match-checker-'.Str::lower(Str::random(8)).'@example.test',
        );
        Sanctum::actingAs($checker);

        $this->withHeader(
            'X-Privileged-Reason',
            'Independent approval of documented supplier price variance',
        )->postJson(
            "/api/v1/procurement/match-runs/{$run}/approve-exception",
        )->assertSuccessful();

        $this->assertSame(
            'exception_approved',
            SupplierInvoice::query()
                ->where('public_id', $invoice)
                ->firstOrFail()
                ->status
                ->value,
        );
    }

    public function test_cumulative_invoicing_cannot_hide_overbilling(): void
    {
        [, $line] = $this->receivedOrder('MATCH-CUM');

        $invoiceOne = $this->linkedInvoice(
            $line->public_id,
            0.6,
            50000,
            'MATCH-CUM-001',
            'match-cum-invoice-1',
        )->assertSuccessful()->json('data.id');

        $this->withHeader('Idempotency-Key', 'match-cum-run-1')
            ->postJson(
                "/api/v1/procurement/supplier-invoices/{$invoiceOne}/match",
            )->assertSuccessful()
            ->assertJsonPath('data.result', 'matched');

        $invoiceTwo = $this->linkedInvoice(
            $line->public_id,
            0.6,
            50000,
            'MATCH-CUM-002',
            'match-cum-invoice-2',
        )->assertSuccessful()->json('data.id');

        $this->withHeader('Idempotency-Key', 'match-cum-run-2')
            ->postJson(
                "/api/v1/procurement/supplier-invoices/{$invoiceTwo}/match",
            )->assertSuccessful()
            ->assertJsonPath('data.result', 'exception')
            ->assertJsonFragment([
                'exception_codes' => ['invoiced_over_received'],
            ]);
    }

    private function receivedOrder(string $suffix): array
    {
        $location = $this->location($suffix);
        $orderId = $this->approvedOrder("{$suffix}-po-key");
        $order = PurchaseOrder::query()
            ->with('lines')
            ->where('public_id', $orderId)
            ->firstOrFail();
        $line = $order->lines->first();

        $this->withHeader('Idempotency-Key', "{$suffix}-receipt-key")
            ->postJson('/api/v1/procurement/goods-receipts', [
                'purchase_order_id' => $orderId,
                'location_id' => $location->public_id,
                'lines' => [[
                    'purchase_order_line_id' => $line->public_id,
                    'quantity' => 1,
                ]],
            ])->assertSuccessful();

        return [$order, $line, $location];
    }

    private function linkedInvoice(
        string $lineId,
        float $quantity,
        float $unitPrice,
        string $number,
        string $key,
    ) {
        return $this->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/procurement/supplier-invoices', [
                'supplier_id' => $this->supplier->public_id,
                'invoice_number' => $number,
                'invoice_date' => now()->toDateString(),
                'lines' => [[
                    'purchase_order_line_id' => $lineId,
                    'product_id' => $this->product->public_id,
                    'packaging_id' => $this->packaging->public_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ]],
            ]);
    }
}
