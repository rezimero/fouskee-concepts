<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierInvoiceRequest extends FormRequest
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
            'supplier_id' => ['required', 'string', 'size:26'],
            'invoice_number' => ['required', 'string', 'max:160'],
            'invoice_date' => ['required', 'date'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'tax_amount' => ['nullable', 'numeric', 'gte:0'],
            'lines' => ['required', 'array', 'min:1', 'max:300'],
            'lines.*.purchase_order_line_id' => ['nullable', 'string', 'size:26'],
            'lines.*.product_id' => ['required', 'string', 'size:26'],
            'lines.*.packaging_id' => ['nullable', 'string', 'size:26'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'gte:0'],
        ];
    }
}
