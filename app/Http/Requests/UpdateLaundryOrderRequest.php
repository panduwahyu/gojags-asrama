<?php

namespace App\Http\Requests;

use App\Enums\LaundryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLaundryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Pembatalan dilakukan penghuni lewat endpoint tersendiri.
            'status' => ['required', Rule::in([LaundryStatus::Pending->value, LaundryStatus::Done->value])],
            'cost' => [
                Rule::requiredIf($this->input('status') === LaundryStatus::Done->value),
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
                'decimal:0,2',
            ],
        ];
    }
}
