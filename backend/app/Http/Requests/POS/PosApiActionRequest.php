<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class PosApiActionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'action' => ['required', 'string'],
        ];
    }
}
