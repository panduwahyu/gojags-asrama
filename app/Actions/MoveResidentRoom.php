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

/** Petugas memindahkan penghuni yang sudah berkamar (belum check-in) ke kamar lain. */
class MoveResidentRoom
{
    /**
     * @return list<string> peringatan yang sudah dikonfirmasi
     *
     * @throws PlacementNeedsConfirmation bila ada peringatan dan belum dikonfirmasi
     */
    public function handle(User $resident, Room $target, bool $confirmed = false): array
    {
        abort_unless($resident->role === Role::Resident, 404);

        return DB::transaction(function () use ($resident, $target, $confirmed) {
            [$rooms, $users, $slots] = RoomLocks::acquireAll();
            $usersById = $users->keyBy('id');
            $occupancy = new RoomOccupancy($rooms, $slots, $usersById);

            $resident = $usersById[$resident->id];
            $target = $rooms->firstWhere('id', $target->id);

            $slot = $slots->first(fn ($slot) => $slot->user_id === $resident->id);
            if ($slot === null) {
                throw ValidationException::withMessages([
                    'room' => 'Penghuni belum memiliki kamar. Gunakan penempatan kamar.',
                ]);
            }

            if ($slot->checked_in_at !== null) {
                throw ValidationException::withMessages([
                    'room' => 'Penghuni yang sudah check-in tidak dapat dipindahkan. Lakukan check-out terlebih dahulu.',
                ]);
            }

            if ($slot->room_id === $target->id) {
                throw ValidationException::withMessages([
                    'room' => 'Penghuni sudah berada di kamar ini.',
                ]);
            }

            // Slot lama dilepas lebih dulu agar teman sekamar dan kapasitas dihitung tanpa dirinya.
            // Bila penempatan ditolak atau butuh konfirmasi, transaksi dibatalkan dan slot lama kembali.
            $occupancy->unassign($resident);

            $violation = Placement::violation($resident, $target, $occupancy);
            if ($violation !== null) {
                throw ValidationException::withMessages(['room' => $violation]);
            }

            $warnings = Placement::warnings($resident, $target, $occupancy);
            if ($warnings !== [] && ! $confirmed) {
                throw new PlacementNeedsConfirmation($warnings);
            }

            $occupancy->assign($resident, $target);

            return $warnings;
        }, 3);
    }
}
