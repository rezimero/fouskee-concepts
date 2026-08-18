<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class CreateStockCountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_id' => ['required', 'string', 'size:26'],
            'reason_code' => ['required', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'lines' => ['required', 'array', 'min:1', 'max:500'],
            'lines.*.product_id' => ['required', 'string', 'size:26'],
            'lines.*.lot_id' => ['nullable', 'string', 'size:26'],
            'lines.*.counted_quantity_base' => ['required', 'numeric', 'gte:0'],
            'lines.*.unit_cost_base' => ['nullable', 'numeric', 'gte:0'],
        ];
    }
}
