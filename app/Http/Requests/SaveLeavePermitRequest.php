<?php

namespace App\Http\Requests;

use App\Support\DateFormat;
use Illuminate\Foundation\Http\FormRequest;

class SaveLeavePermitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'leaves_at' => ['required', DateFormat::UTC],
            'returns_at' => ['required', DateFormat::UTC, 'after:leaves_at'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'returns_at.after' => 'Waktu kembali harus setelah waktu keluar.',
        ];
    }
}
