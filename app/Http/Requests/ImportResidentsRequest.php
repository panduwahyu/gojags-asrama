<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportResidentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Pilih berkas Excel atau CSV terlebih dahulu.',
            'file.mimes' => 'Berkas harus berformat xlsx, xls, atau csv.',
            'file.max' => 'Ukuran berkas maksimal 2 MB.',
        ];
    }
}
