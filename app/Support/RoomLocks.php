<?php

namespace App\Support;

use App\Models\Room;
use App\Models\RoomSlot;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class RoomLocks
{
    /**
     * Mengunci dengan urutan konsisten rooms -> users -> room_slots untuk mencegah deadlock.
     *
     * @return array{0: Collection<int, Room>, 1: Collection<int, User>, 2: Collection<int, RoomSlot>}
     */
    public static function acquireAll(): array
    {
        $rooms = self::lockRooms();

        $users = User::query()
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $slots = RoomSlot::query()
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        return [$rooms, $users, $slots];
    }

    /**
     * Mengunci dengan urutan konsisten rooms -> users -> room_slots untuk satu penghuni.
     */
    public static function acquireFor(User $user): ?RoomSlot
    {
        self::lockRooms();

        User::query()
            ->whereKey($user->id)
            ->lockForUpdate()
            ->first();

        return RoomSlot::query()
            ->with('room')
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->first();
    }

    /**
     * @return Collection<int, Room>
     */
    private static function lockRooms(): Collection
    {
        return Room::query()
            ->orderByDesc('floor')
            ->orderBy('number')
            ->lockForUpdate()
            ->get();
    }
}
