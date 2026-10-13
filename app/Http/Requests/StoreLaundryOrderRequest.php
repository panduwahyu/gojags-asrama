<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLaundryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'string', 'max:255'],
            'set_count' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
