<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status->value,
            'brand' => $this->brand ? [
                'id' => $this->brand->public_id,
                'name' => $this->brand->name,
            ] : null,
            'category' => $this->category ? [
                'id' => $this->category->public_id,
                'name' => $this->category->name,
            ] : null,
            'base_unit' => [
                'id' => $this->baseUnit->public_id,
                'code' => $this->baseUnit->code,
                'symbol' => $this->baseUnit->symbol,
            ],
            'packagings' => $this->packagings->map(fn ($packaging) => [
                'id' => $packaging->public_id,
                'code' => $packaging->code,
                'name' => $packaging->name,
                'barcode' => $packaging->barcode,
                'conversion_to_base' => $packaging->conversion_to_base,
                'unit' => [
                    'id' => $packaging->unit->public_id,
                    'code' => $packaging->unit->code,
                ],
            ]),
        ];
    }
}
