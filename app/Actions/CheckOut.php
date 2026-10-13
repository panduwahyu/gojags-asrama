<?php

namespace App\Actions;

use App\Models\User;
use App\Support\RoomLocks;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckOut
{
    public function handle(User $resident): void
    {
        DB::transaction(function () use ($resident) {
            $slot = RoomLocks::acquireFor($resident);

            if ($slot === null) {
                throw ValidationException::withMessages([
                    'room' => 'Anda belum memiliki alokasi kamar.',
                ]);
            }

            if ($slot->checked_in_at === null) {
                throw ValidationException::withMessages([
                    'room' => 'Anda harus check-in sebelum check-out.',
                ]);
            }

            $stay = $resident->stays()
                ->where('room_id', $slot->room_id)
                ->whereNull('checked_out_at')
                ->lockForUpdate()
                ->first();

            if ($stay === null) {
                throw ValidationException::withMessages([
                    'room' => 'Riwayat check-in aktif tidak ditemukan.',
                ]);
            }

            $stay->update(['checked_out_at' => now()]);
            $slot->update([
                'user_id' => null,
                'checked_in_at' => null,
            ]);
        }, 3);
    }
}
