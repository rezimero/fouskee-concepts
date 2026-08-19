<?php

namespace Tests\Feature\Sales;

use App\Models\SalesQuotationLine;
use LogicException;
use Tests\Support\SalesTestCase;

class SalesQuotationWorkflowTest extends SalesTestCase
{
    public function test_server_prices_quotation_and_conversion_preserves_snapshot(): void
    {
        $quote = $this->quotation('quote-price-001')
            ->assertSuccessful()
            ->assertJsonPath('data.lines.0.effective_unit_price', '62000.000000')
            ->json('data.id');

        $this->postJson("/api/v1/sales/quotations/{$quote}/send")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'sent');

        $this->postJson("/api/v1/sales/quotations/{$quote}/accept")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'accepted');

        $order = $this->postJson(
            "/api/v1/sales/quotations/{$quote}/convert",
        )->assertSuccessful();

        $order
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.lines.0.effective_unit_price', '62000.000000')
            ->assertJsonPath('data.lines.0.quantity_base', '50.000000');
    }

    public function test_sent_quotation_line_is_immutable(): void
    {
        $quote = $this->quotation('quote-freeze-001')
            ->assertSuccessful()
            ->json('data.id');

        $this->postJson("/api/v1/sales/quotations/{$quote}/send")
            ->assertSuccessful();

        $line = SalesQuotationLine::query()->firstOrFail();

        $this->expectException(LogicException::class);
        $line->update(['effective_unit_price' => 1]);
    }

    public function test_expired_quotation_cannot_be_accepted(): void
    {
        $quote = $this->quotation(
            'quote-expired-001',
            today()->toDateString(),
        )->assertSuccessful()->json('data.id');

        $this->travelTo(now()->addDay());

        $this->postJson("/api/v1/sales/quotations/{$quote}/send")
            ->assertSuccessful();

        $this->postJson("/api/v1/sales/quotations/{$quote}/accept")
            ->assertConflict()
            ->assertJsonPath('code', 'sales_quotation_expired');
    }

    public function test_conversion_is_idempotent(): void
    {
        $quote = $this->quotation('quote-convert-001')
            ->assertSuccessful()
            ->json('data.id');

        $this->postJson("/api/v1/sales/quotations/{$quote}/send")
            ->assertSuccessful();
        $this->postJson("/api/v1/sales/quotations/{$quote}/accept")
            ->assertSuccessful();

        $first = $this->postJson("/api/v1/sales/quotations/{$quote}/convert")
            ->assertSuccessful()
            ->json('data.id');

        $second = $this->postJson("/api/v1/sales/quotations/{$quote}/convert")
            ->assertSuccessful()
            ->json('data.id');

        $this->assertSame($first, $second);
        $this->assertDatabaseCount('sales_orders', 1);
    }

    private function quotation(
        string $key,
        ?string $validUntil = null,
    ) {
        return $this->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/sales/quotations', [
                'customer_id' => $this->customer->public_id,
                'settlement_type' => 'prepaid',
                'valid_until' => $validUntil ?? today()->addDays(7)->toDateString(),
                'lines' => [[
                    'product_id' => $this->product->public_id,
                    'packaging_id' => $this->packaging->public_id,
                    'quantity' => 1,
                ]],
            ]);
    }
}
