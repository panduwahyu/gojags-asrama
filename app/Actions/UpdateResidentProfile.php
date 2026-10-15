<?php

namespace App\Actions;

use App\Enums\Gender;
use App\Enums\Religion;
use App\Enums\Role;
use App\Models\RoomSlot;
use App\Models\User;
use App\Support\Placement;
use App\Support\RoomLocks;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateResidentProfile
{
    /**
     * Profil boleh diperbaiki kapan saja (keputusan pengelola no. 7). Bila perubahan membuat
     * profil berbeda dengan teman sekamar, kamar tetap dipertahankan dan peringatan dikembalikan.
     *
     * @param  array{cohort: string, gender: string|Gender, religion: string|Religion}  $profile
     * @return list<string> peringatan
     */
    public function handle(User $resident, array $profile): array
    {
        abort_unless($resident->role === Role::Resident, 404);

        $cohort = User::normalizeProfileText($profile['cohort']);
        $religion = $profile['religion'] instanceof Religion
            ? $profile['religion']
            : Religion::tryFrom(User::normalizeProfileText($profile['religion']));

        if ($cohort === '' || $religion === null) {
            throw ValidationException::withMessages([
                'profile' => 'Angkatan dan agama wajib diisi.',
            ]);
        }

        $gender = $profile['gender'] instanceof Gender
            ? $profile['gender']
            : Gender::from($profile['gender']);

        return DB::transaction(function () use ($resident, $cohort, $gender, $religion) {
            $slot = RoomLocks::acquireFor($resident);

            $resident->update([
                'cohort' => $cohort,
                'gender' => $gender,
                'religion' => $religion,
            ]);

            if ($slot === null) {
                return [];
            }

            $mates = User::query()->whereIn(
                'id',
                RoomSlot::query()
                    ->where('room_id', $slot->room_id)
                    ->whereNotNull('user_id')
                    ->where('user_id', '!=', $resident->id)
                    ->pluck('user_id')
            )->get();

            return Placement::roommateMismatches($resident, $mates, includeGender: true);
        }, 3);
    }
}
