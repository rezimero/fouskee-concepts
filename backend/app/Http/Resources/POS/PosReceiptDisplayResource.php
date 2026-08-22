<?php

namespace App\Http\Resources\POS;

use Illuminate\Http\Resources\Json\JsonResource;

class PosReceiptDisplayResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'receipt_number' => $this->receipt_number,
            'amount' => $this->amount,
        ];
    }
}
