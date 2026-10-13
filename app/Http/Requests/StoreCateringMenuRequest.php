<?php

namespace App\Http\Requests;

use App\Support\DateFormat;
use Illuminate\Foundation\Http\FormRequest;

class StoreCateringMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'menu_number' => ['required', 'integer', 'min:1', 'max:10000'],
            'provider' => ['required', 'string', 'max:255'],
            'served_at' => ['required', DateFormat::UTC],
        ];
    }
}
