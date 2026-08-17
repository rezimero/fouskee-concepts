<?php

namespace App\Http\Requests\Commercial;

use App\Enums\CustomerSegment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'segment' => ['required', Rule::enum(CustomerSegment::class)],
            'display_name' => ['required', 'string', 'max:200'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'tax_identifier' => ['nullable', 'string', 'max:100'],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'price_list_id' => ['nullable', 'string', 'size:26'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
