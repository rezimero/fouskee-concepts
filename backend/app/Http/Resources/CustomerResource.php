<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'number' => $this->customer_number,
            'segment' => $this->segment->value,
            'display_name' => $this->display_name,
            'legal_name' => $this->legal_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'tax_identifier' => $this->tax_identifier,
            'credit_limit' => $this->credit_limit,
            'status' => $this->status->value,
            'price_list_id' => $this->defaultPriceList?->public_id,
        ];
    }
}
