<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodsReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'number' => $this->goods_receipt_number,
            'status' => $this->status->value,
            'purchase_order_id' => $this->purchaseOrder?->public_id,
            'purchase_order_number' => $this->purchaseOrder?->purchase_order_number,
            'location' => $this->whenLoaded('location', fn () => [
                'id' => $this->location->public_id,
                'code' => $this->location->code,
                'name' => $this->location->name,
            ]),
            'received_at' => $this->received_at?->toISOString(),
            'posted_at' => $this->posted_at?->toISOString(),
            'movement_id' => $this->movement?->public_id,
            'notes' => $this->notes,
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(
                fn ($line) => [
                    'id' => $line->public_id,
                    'purchase_order_line_id' => $line->purchaseOrderLine?->public_id,
                    'product_id' => $line->product?->public_id,
                    'product_name' => $line->product?->name,
                    'packaging_id' => $line->packaging?->public_id,
                    'quantity' => $line->received_quantity,
                    'quantity_base' => $line->received_quantity_base,
                    'unit_cost_base' => $line->unit_cost_base_snapshot,
                    'lot_code' => $line->lot_code,
                    'expiry_date' => $line->expiry_date?->toDateString(),
                ],
            )),
        ];
    }
}
