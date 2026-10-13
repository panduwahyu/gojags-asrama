<?php

namespace App\Actions;

use App\Models\User;
use App\Support\RoomLocks;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckIn
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

            if ($slot->checked_in_at !== null) {
                throw ValidationException::withMessages([
                    'room' => 'Anda sudah check-in.',
                ]);
            }

            $slot->update(['checked_in_at' => now()]);
            $resident->stays()->create([
                'room_id' => $slot->room->id,
                'user_name' => $resident->name,
                'room_number' => $slot->room->number,
                'floor' => $slot->room->floor,
                'checked_in_at' => $slot->checked_in_at,
            ]);
        }, 3);
    }
}
