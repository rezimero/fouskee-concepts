<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class SalesFulfillmentActionRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
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
        });
    }
}
