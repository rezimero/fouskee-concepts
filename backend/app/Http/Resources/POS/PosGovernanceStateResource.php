<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Resources\Json\JsonResource;

class PosGovernanceStateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'governance' => 'active',
        ];
    }
}
