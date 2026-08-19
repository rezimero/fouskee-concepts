<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesQuotationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'number' => $this->quotation_number,
            'status' => $this->status->value,
            'settlement_type' => $this->settlement_type->value,
            'currency_code' => $this->currency_code,
            'subtotal_amount' => $this->subtotal_amount,
            'total_amount' => $this->total_amount,
            'valid_until' => $this->valid_until?->toDateString(),
            'notes' => $this->notes,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->public_id,
                'name' => $this->customer->display_name,
            ]),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines
                ->map(fn ($line) => [
                    'line_number' => $line->line_number,
                    'product_sku' => $line->product_sku_snapshot,
                    'product_name' => $line->product_name_snapshot,
                    'packaging_name' => $line->packaging_name_snapshot,
                    'quantity' => $line->quoted_quantity,
                    'quantity_base' => $line->quoted_quantity_base,
                    'list_unit_price' => $line->list_unit_price,
                    'floor_unit_price' => $line->floor_unit_price,
                    'effective_unit_price' => $line->effective_unit_price,
                    'line_total' => $line->line_total,
                    'price_exception' => $line->price_exception,
                ])->values()),
            'created_at' => $this->created_at?->toISOString(),
            'sent_at' => $this->sent_at?->toISOString(),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'converted_at' => $this->converted_at?->toISOString(),
        ];
    }
}
