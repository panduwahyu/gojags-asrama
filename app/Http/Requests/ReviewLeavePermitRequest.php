<?php

namespace App\Http\Requests;

use App\Enums\PermitStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewLeavePermitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([PermitStatus::Approved->value, PermitStatus::Rejected->value]),
            ],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required_if' => 'Alasan penolakan wajib diisi.',
        ];
    }
}
