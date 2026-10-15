<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\Religion;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['religion' => User::normalizeProfileText((string) $this->input('religion'))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'cohort' => ['required', 'string', 'max:100', 'regex:/^[\pL\pN\s]+$/u'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'religion' => ['required', Rule::enum(Religion::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email sudah terdaftar.',
            'cohort.regex' => 'Angkatan hanya boleh berisi huruf, angka, dan spasi.',
        ];
    }
}
