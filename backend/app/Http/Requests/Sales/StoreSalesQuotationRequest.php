<?php

namespace App\Http\Requests\Sales;

use App\Enums\SalesSettlementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalesQuotationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'string', 'size:26'],
            'settlement_type' => [
                'required',
                Rule::enum(SalesSettlementType::class),
            ],
            'valid_until' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.product_id' => ['required', 'string', 'size:26'],
            'lines.*.packaging_id' => ['required', 'string', 'size:26'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.requested_unit_price' => [
                'nullable',
                'numeric',
                'gte:0',
            ],
        ];
    }

    public function idempotencyKey(): string
    {
        return trim((string) $this->header('Idempotency-Key'));
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->idempotencyKey() === '') {
                $validator->errors()->add(
                    'idempotency_key',
                    'The Idempotency-Key header is required.',
                );
            }

            if (strlen($this->idempotencyKey()) > 120) {
                $validator->errors()->add(
                    'idempotency_key',
                    'The Idempotency-Key header may not exceed 120 characters.',
                );
            }
        });
    }
}
