<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Resources\Json\JsonResource;

class PosRecoveryStatusResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'recovery' => 'ready',
        ];
    }
}
