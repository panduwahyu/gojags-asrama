<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cohort' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'religion' => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'cohort.required' => 'Angkatan dan agama wajib diisi.',
            'religion.required' => 'Angkatan dan agama wajib diisi.',
        ];
    }
}
