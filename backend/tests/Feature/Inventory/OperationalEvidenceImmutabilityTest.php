<?php

namespace Tests\Feature\Inventory;

use App\Models\StockCountLine;
use App\Models\StockTransferLine;
use LogicException;
use Tests\Support\InventoryTestCase;

class OperationalEvidenceImmutabilityTest extends InventoryTestCase
{
    public function test_stock_count_evidence_line_cannot_be_updated(): void
    {
        $location = $this->location('IMM-COUNT');

        $this->postJson('/api/v1/inventory/counts', [
            'location_id' => $location->public_id,
            'reason_code' => 'opening_count',
            'lines' => [[
                'product_id' => $this->product->public_id,
                'counted_quantity_base' => 10,
                'unit_cost_base' => 1000,
            ]],
        ])->assertSuccessful();

        $line = StockCountLine::query()->firstOrFail();

        $this->expectException(LogicException::class);
        $line->update(['counted_quantity_base' => 999]);
    }

    public function test_stock_transfer_evidence_line_cannot_be_deleted(): void
    {
        $source = $this->location('IMM-TR-S');
        $destination = $this->location('IMM-TR-D');

        $this->postJson('/api/v1/inventory/transfer-orders', [
            'source_location_id' => $source->public_id,
            'destination_location_id' => $destination->public_id,
            'lines' => [[
                'product_id' => $this->product->public_id,
                'quantity' => 10,
            ]],
        ])->assertSuccessful();

        $line = StockTransferLine::query()->firstOrFail();

        $this->expectException(LogicException::class);
        $line->delete();
    }
}
