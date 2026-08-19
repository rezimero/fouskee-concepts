<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class ReserveSalesOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'location_id' => ['required', 'string', 'size:26'],
        ];
    }
}
