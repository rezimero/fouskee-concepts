<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoodsReceiptRequest extends FormRequest
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
            'purchase_order_id' => ['required', 'string', 'size:26'],
            'location_id' => ['required', 'string', 'size:26'],
            'received_at' => ['nullable', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'lines' => ['required', 'array', 'min:1', 'max:200'],
            'lines.*.purchase_order_line_id' => [
                'required', 'string', 'size:26',
            ],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.lot_code' => ['nullable', 'string', 'max:100'],
            'lines.*.expiry_date' => ['nullable', 'date'],
        ];
    }
}
