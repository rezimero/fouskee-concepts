<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'number' => $this->transfer_number,
            'status' => $this->status->value,
            'source' => $this->locationData($this->sourceLocation),
            'destination' => $this->locationData($this->destinationLocation),
            'transit' => $this->locationData($this->transitLocation),
            'notes' => $this->notes,
            'dispatched_at' => $this->dispatched_at?->toISOString(),
            'received_at' => $this->received_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(
                fn ($line) => [
                    'product_id' => $line->product?->public_id,
                    'product_name' => $line->product?->name,
                    'packaging_id' => $line->packaging?->public_id,
                    'lot_id' => $line->lot?->public_id,
                    'entered_quantity' => $line->entered_quantity,
                    'conversion_to_base' => $line->conversion_to_base_snapshot,
                    'quantity_base' => $line->quantity_base,
                ],
            )),
        ];
    }

    private function locationData($location): ?array
    {
        if ($location === null) {
            return null;
        }

        return [
            'id' => $location->public_id,
            'code' => $location->code,
            'name' => $location->name,
        ];
    }
}
