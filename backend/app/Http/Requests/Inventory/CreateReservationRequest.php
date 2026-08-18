<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class CreateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => ['required', 'string', 'size:26'],
            'product_id' => ['required', 'string', 'size:26'],
            'quantity_base' => ['required', 'numeric', 'gt:0'],
            'source_reference_type' => ['nullable', 'string', 'max:80'],
            'source_reference_public_id' => ['nullable', 'string', 'max:64'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
