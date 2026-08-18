<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'number' => $this->movement_number,
            'type' => $this->movement_type->value,
            'occurred_at' => $this->occurred_at?->toISOString(),
            'posted_at' => $this->posted_at?->toISOString(),
            'business_reason' => $this->business_reason,
            'source' => [
                'type' => $this->source_reference_type,
                'id' => $this->source_reference_public_id,
            ],
            'reversal_of' => $this->reversalOf?->public_id,
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(
                fn ($line) => [
                    'location_id' => $line->location?->public_id,
                    'product_id' => $line->product?->public_id,
                    'packaging_id' => $line->packaging?->public_id,
                    'lot_id' => $line->lot?->public_id,
                    'entered_quantity' => $line->entered_quantity,
                    'conversion_to_base' => $line->conversion_to_base_snapshot,
                    'quantity_base' => $line->quantity_base,
                    'unit_cost' => $line->unit_cost_snapshot,
                    'inventory_value_delta' => $line->inventory_value_delta,
                ],
            )),
        ];
    }
}
