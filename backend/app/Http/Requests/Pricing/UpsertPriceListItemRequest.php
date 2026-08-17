<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;

class UpsertPriceListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'selling_price' => ['required', 'numeric', 'gt:0'],
            'minimum_selling_price' => [
                'nullable',
                'numeric',
                'gt:0',
                'lte:selling_price',
            ],
            'maximum_discount_percent' => [
                'nullable',
                'numeric',
                'between:0,100',
            ],
        ];
    }
}
