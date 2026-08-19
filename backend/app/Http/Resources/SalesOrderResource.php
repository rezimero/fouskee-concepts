<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'number' => $this->sales_order_number,
            'status' => $this->status->value,
            'settlement_type' => $this->settlement_type->value,
            'currency_code' => $this->currency_code,
            'subtotal_amount' => $this->subtotal_amount,
            'total_amount' => $this->total_amount,
            'credit_limit_snapshot' => $this->credit_limit_snapshot,
            'credit_exposure_before_snapshot' => $this->credit_exposure_before_snapshot,
            'exception_codes' => $this->exception_codes ?? [],
            'notes' => $this->notes,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->public_id,
                'name' => $this->customer->display_name,
                'segment' => $this->customer->segment->value,
            ]),
            'price_list' => $this->whenLoaded('priceList', fn () => [
                'id' => $this->priceList->public_id,
                'code' => $this->priceList->code,
                'name' => $this->priceList->name,
            ]),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines
                ->map(fn ($line) => [
                    'line_number' => $line->line_number,
                    'product_id' => $line->product->public_id,
                    'packaging_id' => $line->packaging->public_id,
                    'product_sku' => $line->product_sku_snapshot,
                    'product_name' => $line->product_name_snapshot,
                    'packaging_name' => $line->packaging_name_snapshot,
                    'conversion_to_base' => $line->conversion_to_base_snapshot,
                    'quantity' => $line->ordered_quantity,
                    'quantity_base' => $line->ordered_quantity_base,
                    'list_unit_price' => $line->list_unit_price,
                    'floor_unit_price' => $line->floor_unit_price,
                    'effective_unit_price' => $line->effective_unit_price,
                    'line_total' => $line->line_total,
                    'price_exception' => $line->price_exception,
                ])->values()),
            'created_at' => $this->created_at?->toISOString(),
            'submitted_at' => $this->submitted_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
        ];
    }
}
