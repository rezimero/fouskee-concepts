<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Resources\Json\JsonResource;

class PosCertificationStateResource extends JsonResource
{
    public function toArray($request): array
    {
        return $this->resource;
    }
}
