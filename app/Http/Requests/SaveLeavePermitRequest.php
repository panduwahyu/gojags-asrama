<?php

namespace App\Http\Requests;

use App\Enums\PermitStatus;
use App\Support\DateFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $leavesAt = Carbon::parse($this->input('leaves_at'));
            $returnsAt = Carbon::parse($this->input('returns_at'));

            // Toleransi satu menit: isian datetime-local hanya setingkat menit.
            if ($leavesAt->lt(now()->subMinute())) {
                $validator->errors()->add('leaves_at', 'Waktu keluar tidak boleh di masa lalu.');

                return;
            }

            if ($this->overlapsActivePermit($leavesAt, $returnsAt)) {
                $validator->errors()->add('leaves_at', 'Waktu izin bertabrakan dengan izin lain yang masih aktif.');
            }
        });
    }

    private function overlapsActivePermit(Carbon $leavesAt, Carbon $returnsAt): bool
    {
        return $this->user()->leavePermits()
            ->whereIn('status', [PermitStatus::Pending, PermitStatus::Approved])
            ->where('leaves_at', '<', $returnsAt)
            ->where('returns_at', '>', $leavesAt)
            ->when($this->route('permit'), fn ($query, $permit) => $query->whereKeyNot($permit->id))
            ->exists();
    }

    public function messages(): array
    {
        return [
            'returns_at.after' => 'Waktu kembali harus setelah waktu keluar.',
        ];
    }
}
