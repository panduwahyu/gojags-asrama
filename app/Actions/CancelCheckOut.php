<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\User;
use App\Support\RoomLocks;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Petugas membatalkan check-out yang keliru selama slot sebelumnya belum diisi orang lain. */
class CancelCheckOut
{
    public function handle(User $resident): void
    {
        abort_unless($resident->role === Role::Resident, 404);

        DB::transaction(function () use ($resident) {
            [, , $slots] = RoomLocks::acquireAll();

            if ($slots->contains(fn ($slot) => $slot->user_id === $resident->id)) {
                throw ValidationException::withMessages([
                    'room' => 'Penghuni masih memiliki kamar.',
                ]);
            }

            $stay = $resident->stays()->latest('id')->lockForUpdate()->first();
            if ($stay === null || $stay->checked_out_at === null) {
                throw ValidationException::withMessages([
                    'room' => 'Tidak ada check-out yang dapat dibatalkan.',
                ]);
            }

            $freeSlot = $stay->room_id === null ? null : $slots->first(
                fn ($slot) => $slot->room_id === $stay->room_id && $slot->user_id === null
            );
            if ($freeSlot === null) {
                throw ValidationException::withMessages([
                    'room' => 'Slot kamar sebelumnya sudah ditempati penghuni lain.',
                ]);
            }

            $freeSlot->update(['user_id' => $resident->id, 'checked_in_at' => $stay->checked_in_at]);
            $stay->update(['checked_out_at' => null]);
        }, 3);
    }
}
