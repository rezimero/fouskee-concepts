<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Resources\Json\JsonResource;

class PosCartResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'items' => $this->items ?? [],
            'total' => $this->total ?? 0,
        ];
    }
}
