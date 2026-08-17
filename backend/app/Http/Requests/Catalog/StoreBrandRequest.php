<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBrandRequest extends FormRequest
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
                Rule::unique('brands', 'code')
                    ->where('organization_id', $organizationId),
            ],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('brands', 'name')
                    ->where('organization_id', $organizationId),
            ],
        ];
    }
}
