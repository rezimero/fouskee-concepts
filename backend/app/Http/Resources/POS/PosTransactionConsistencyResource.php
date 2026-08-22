<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Resources\Json\JsonResource;

class PosTransactionConsistencyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'consistency_status' => 'valid',
        ];
    }
}
