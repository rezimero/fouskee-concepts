<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryLocationRequest extends FormRequest
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
                'max:60',
                Rule::unique('inventory_locations', 'code')
                    ->where('organization_id', $organizationId),
            ],
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::in(['warehouse', 'store', 'transit'])],
        ];
    }
}
