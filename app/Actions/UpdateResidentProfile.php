<?php

namespace App\Actions;

use App\Enums\Gender;
use App\Enums\Role;
use App\Models\User;
use App\Support\RoomLocks;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateResidentProfile
{
    /**
     * @param  array{cohort: string, gender: string|Gender, religion: string}  $profile
     */
    public function handle(User $resident, array $profile): void
    {
        abort_unless($resident->role === Role::Resident, 404);

        $cohort = User::normalizeProfileText($profile['cohort']);
        $religion = User::normalizeProfileText($profile['religion']);

        if ($cohort === '' || $religion === '') {
            throw ValidationException::withMessages([
                'profile' => 'Angkatan dan agama wajib diisi.',
            ]);
        }

        $gender = $profile['gender'] instanceof Gender
            ? $profile['gender']
            : Gender::from($profile['gender']);

        DB::transaction(function () use ($resident, $cohort, $gender, $religion) {
            $slot = RoomLocks::acquireFor($resident);

            if ($slot !== null) {
                throw ValidationException::withMessages([
                    'profile' => 'Profil penghuni yang sudah dialokasikan tidak dapat diubah.',
                ]);
            }

            $resident->update([
                'cohort' => $cohort,
                'gender' => $gender,
                'religion' => $religion,
            ]);
        }, 3);
    }
}
