<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'number' => $this->purchase_order_number,
            'status' => $this->status->value,
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->public_id,
                'number' => $this->supplier->supplier_number,
                'name' => $this->supplier->display_name,
            ]),
            'currency_code' => $this->currency_code,
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'total_amount' => $this->total_amount,
            'notes' => $this->notes,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(
                fn ($line) => [
                    'id' => $line->public_id,
                    'line_number' => $line->line_number,
                    'product_id' => $line->product?->public_id,
                    'sku' => $line->product_sku_snapshot,
                    'product_name' => $line->product_name_snapshot,
                    'packaging_id' => $line->packaging?->public_id,
                    'packaging_name' => $line->packaging_name_snapshot,
                    'conversion_to_base' => $line->conversion_to_base_snapshot,
                    'quantity' => $line->ordered_quantity,
                    'quantity_base' => $line->ordered_quantity_base,
                    'unit_price' => $line->unit_price,
                    'unit_price_base' => $line->unit_price_base,
                    'line_total' => $line->line_total,
                ],
            )),
        ];
    }
}
