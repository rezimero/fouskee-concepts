<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Resources\Json\JsonResource;

class PosDurableStateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'state_status' => 'durable',
        ];
    }
}
