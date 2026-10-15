<?php

namespace App\Actions;

use App\Enums\Role;
use App\Exceptions\PlacementNeedsConfirmation;
use App\Models\Room;
use App\Models\User;
use App\Support\Placement;
use App\Support\RoomLocks;
use App\Support\RoomOccupancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Petugas menempatkan penghuni yang belum berkamar ke kamar tertentu. */
class AssignRoomSlot
{
    /**
     * @return list<string> peringatan yang sudah dikonfirmasi
     *
     * @throws PlacementNeedsConfirmation bila ada peringatan dan belum dikonfirmasi
     */
    public function handle(User $resident, Room $room, bool $confirmed = false): array
    {
        abort_unless($resident->role === Role::Resident, 404);

        return DB::transaction(function () use ($resident, $room, $confirmed) {
            [$rooms, $users, $slots] = RoomLocks::acquireAll();
            $usersById = $users->keyBy('id');
            $occupancy = new RoomOccupancy($rooms, $slots, $usersById);

            $resident = $usersById[$resident->id];
            $room = $rooms->firstWhere('id', $room->id);

            if ($occupancy->isAssigned($resident)) {
                throw ValidationException::withMessages([
                    'room' => 'Penghuni sudah memiliki kamar. Gunakan pemindahan kamar.',
                ]);
            }

            $violation = Placement::violation($resident, $room, $occupancy);
            if ($violation !== null) {
                throw ValidationException::withMessages(['room' => $violation]);
            }

            $warnings = Placement::warnings($resident, $room, $occupancy);
            if ($warnings !== [] && ! $confirmed) {
                throw new PlacementNeedsConfirmation($warnings);
            }

            $occupancy->assign($resident, $room);

            return $warnings;
        }, 3);
    }
}
