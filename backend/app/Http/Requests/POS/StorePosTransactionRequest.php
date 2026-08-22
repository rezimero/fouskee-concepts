<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class StorePosTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable'],
            'lines' => ['required','array','min:1'],
            'lines.*.product_id' => ['required'],
            'lines.*.quantity' => ['required','numeric','gt:0'],
            'lines.*.unit_price' => ['required','numeric','gte:0'],
        ];
    }
}
