<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePriceListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->default_organization_id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('price_lists', 'code')
                    ->where('organization_id', $organizationId),
            ],
            'name' => ['required', 'string', 'max:150'],
            'currency_code' => ['sometimes', 'string', 'size:3'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
