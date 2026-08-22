<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Resources\Json\JsonResource;

class PosConsistencyStateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'status' => 'consistent',
        ];
    }
}
