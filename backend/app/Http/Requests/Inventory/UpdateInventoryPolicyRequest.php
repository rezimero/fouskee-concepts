<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'count_variance_approval_value_threshold' => [
                'required', 'numeric', 'gte:0', 'max:999999999999999999',
            ],
            'backdate_window_days' => ['required', 'integer', 'min:0', 'max:365'],
            'require_separate_transfer_receiver' => ['required', 'boolean'],
            'allow_immediate_transfers' => ['required', 'boolean'],
        ];
    }
}
