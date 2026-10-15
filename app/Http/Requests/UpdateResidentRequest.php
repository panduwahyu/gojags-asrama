<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\Religion;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Agama dipilih dari enum; huruf besar dan spasi berlebih diabaikan.
        $this->merge(['religion' => User::normalizeProfileText((string) $this->input('religion'))]);
    }

    public function rules(): array
    {
        return [
            'cohort' => ['required', 'string', 'max:100', 'regex:/^[\pL\pN\s]+$/u'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'religion' => ['required', Rule::enum(Religion::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'cohort.required' => 'Angkatan dan agama wajib diisi.',
            'religion.required' => 'Angkatan dan agama wajib diisi.',
            'cohort.regex' => 'Angkatan hanya boleh berisi huruf, angka, dan spasi.',
        ];
    }
}
