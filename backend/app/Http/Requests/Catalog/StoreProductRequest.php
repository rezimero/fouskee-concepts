<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->default_organization_id;

        return [
            'sku' => [
                'required',
                'string',
                'max:80',
                Rule::unique('products', 'sku')
                    ->where('organization_id', $organizationId),
            ],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:4000'],
            'brand_id' => ['nullable', 'string', 'size:26'],
            'category_id' => ['nullable', 'string', 'size:26'],
            'base_unit_id' => ['required', 'string', 'size:26'],
            'packagings' => ['required', 'array', 'min:1', 'max:20'],
            'packagings.*.unit_id' => ['required', 'string', 'size:26'],
            'packagings.*.code' => ['required', 'string', 'max:60', 'distinct'],
            'packagings.*.name' => ['required', 'string', 'max:150'],
            'packagings.*.barcode' => [
                'nullable',
                'string',
                'max:100',
                'distinct',
                'unique:product_packagings,barcode',
            ],
            'packagings.*.conversion_to_base' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999999999.999999',
            ],
            'packagings.*.is_default_sale' => ['sometimes', 'boolean'],
            'packagings.*.is_default_purchase' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $packagings = $this->input('packagings', []);

                foreach (['is_default_sale', 'is_default_purchase'] as $field) {
                    $count = count(array_filter(
                        $packagings,
                        fn (array $row) => filter_var($row[$field] ?? false, FILTER_VALIDATE_BOOL),
                    ));

                    if ($count > 1) {
                        $validator->errors()->add(
                            'packagings',
                            "Only one packaging may be selected for {$field}.",
                        );
                    }
                }
            },
        ];
    }
}
