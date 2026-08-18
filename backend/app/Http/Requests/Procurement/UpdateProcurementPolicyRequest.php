<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProcurementPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchase_order_approval_value_threshold' => [
                'required', 'numeric', 'gte:0',
            ],
            'over_receipt_tolerance_percent' => [
                'required', 'numeric', 'gte:0', 'lte:100',
            ],
            'invoice_quantity_tolerance_percent' => [
                'required', 'numeric', 'gte:0', 'lte:100',
            ],
            'invoice_price_tolerance_percent' => [
                'required', 'numeric', 'gte:0', 'lte:100',
            ],
            'invoice_amount_tolerance_percent' => [
                'required', 'numeric', 'gte:0', 'lte:100',
            ],
        ];
    }
}
