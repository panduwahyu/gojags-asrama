<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\User;
use App\Support\RoomLocks;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Petugas mencabut alokasi kamar penghuni yang belum check-in (mis. tidak datang). */
class UnassignRoomSlot
{
    public function handle(User $resident): void
    {
        abort_unless($resident->role === Role::Resident, 404);

        DB::transaction(function () use ($resident) {
            $slot = RoomLocks::acquireFor($resident);

            if ($slot === null) {
                throw ValidationException::withMessages([
                    'room' => 'Penghuni belum memiliki kamar.',
                ]);
            }

            if ($slot->checked_in_at !== null) {
                throw ValidationException::withMessages([
                    'room' => 'Penghuni sudah check-in. Lakukan check-out terlebih dahulu.',
                ]);
            }

            $slot->update(['user_id' => null, 'checked_in_at' => null]);
        }, 3);
    }
}
