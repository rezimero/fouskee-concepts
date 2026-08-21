<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesFulfillmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'number' => $this->fulfillment_number,
            'status' => $this->status->value,
            'order_id' => $this->order?->public_id,
            'order_number' => $this->order?->sales_order_number,
            'location' => $this->location === null ? null : [
                'id' => $this->location->public_id,
                'code' => $this->location->code,
                'name' => $this->location->name,
            ],
            'inventory_movement_id' => $this->movement?->public_id,
            'reversal_movement_id' => $this->reversalMovement?->public_id,
            'picked_at' => $this->picked_at?->toISOString(),
            'dispatched_at' => $this->dispatched_at?->toISOString(),
            'reversed_at' => $this->reversed_at?->toISOString(),
            'reversal_reason' => $this->reversal_reason,
            'lines' => $this->whenLoaded('lines', fn () => $this->lines
                ->map(fn ($line) => [
                    'reservation_id' => $line->reservation?->public_id,
                    'product_id' => $line->product?->public_id,
                    'sku' => $line->product?->sku,
                    'product_name' => $line->product?->name,
                    'lot_id' => $line->lot?->public_id,
                    'lot_code' => $line->lot?->code,
                    'quantity_base' => $line->quantity_base_snapshot,
                ])->values()),
        ];
    }
}
