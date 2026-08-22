<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Resources\Json\JsonResource;

class PosCheckoutWorkflowResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'transaction_id' => $this->id,
            'status' => $this->status,
        ];
    }
}
