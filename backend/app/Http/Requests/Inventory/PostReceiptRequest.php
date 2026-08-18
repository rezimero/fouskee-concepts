<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class PostReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'min:8', 'max:120'],
            'location_id' => ['required', 'string', 'size:26'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.product_id' => ['required', 'string', 'size:26'],
            'lines.*.packaging_id' => ['nullable', 'string', 'size:26'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_cost_base' => ['nullable', 'numeric', 'gte:0'],
        ];
    }
}
