<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class CreateStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_location_id' => ['required', 'string', 'size:26'],
            'destination_location_id' => ['required', 'string', 'size:26'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.product_id' => ['required', 'string', 'size:26'],
            'lines.*.packaging_id' => ['nullable', 'string', 'size:26'],
            'lines.*.lot_id' => ['nullable', 'string', 'size:26'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
