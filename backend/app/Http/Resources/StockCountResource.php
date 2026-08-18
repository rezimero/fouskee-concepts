<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockCountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'number' => $this->count_number,
            'status' => $this->status->value,
            'location' => [
                'id' => $this->location?->public_id,
                'name' => $this->location?->name,
            ],
            'reason_code' => $this->reason_code,
            'notes' => $this->notes,
            'total_abs_variance_value' => $this->total_abs_variance_value,
            'approval_id' => $this->approvalRequest?->public_id,
            'posted_movement_id' => $this->postedMovement?->public_id,
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(
                fn ($line) => [
                    'product_id' => $line->product?->public_id,
                    'product_name' => $line->product?->name,
                    'lot_id' => $line->lot?->public_id,
                    'system_quantity_base' => $line->system_quantity_base,
                    'counted_quantity_base' => $line->counted_quantity_base,
                    'variance_quantity_base' => $line->variance_quantity_base,
                    'unit_cost_snapshot' => $line->unit_cost_snapshot,
                    'variance_value' => $line->variance_value,
                ],
            )),
        ];
    }
}
