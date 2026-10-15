<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\Room;
use App\Models\User;
use App\Support\Placement;
use App\Support\RoomLocks;
use App\Support\RoomOccupancy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AllocateRooms
{
    private const PRIORITY_SAME_COHORT = 0;

    private const PRIORITY_OTHER_COHORT = 1;

    private const PRIORITY_EMPTY_ROOM = 2;

    /**
     * @return array{
     *     allocated: list<array{id: int, name: string, room: string, floor: int, notes: list<string>}>,
     *     waiting: list<array{id: int, name: string}>,
     *     incomplete: list<array{id: int, name: string}>
     * }
     */
    public function handle(): array
    {
        return DB::transaction(function () {
            [$rooms, $users, $slots] = RoomLocks::acquireAll();

            $usersById = $users->keyBy('id');
            foreach ($usersById as $user) {
                $user->cohort = User::normalizeProfileText($user->cohort);
            }

            $occupancy = new RoomOccupancy($rooms, $slots, $usersById);

            $pending = $this->pendingResidents($users, $occupancy);
            $incomplete = $pending->filter(fn (User $user) => ! $this->hasCompleteProfile($user));
            $groups = $pending
                ->diffKeys($incomplete)
                ->groupBy(fn (User $user) => (string) $user->cohort)
                ->sortKeys(SORT_NATURAL);

            $allocated = [];
            $waiting = [];

            foreach ($groups as $cohort => $members) {
                $homeFloor = $this->homeFloorFor((string) $cohort, $occupancy);

                foreach ($members as $resident) {
                    $room = $this->findRoomFor($resident, $homeFloor, $occupancy);

                    if ($room === null) {
                        $waiting[] = ['id' => $resident->id, 'name' => $resident->name];

                        continue;
                    }

                    $notes = Placement::warnings($resident, $room, $occupancy);
                    $occupancy->assign($resident, $room);
                    $allocated[] = [
                        'id' => $resident->id,
                        'name' => $resident->name,
                        'room' => $room->number,
                        'floor' => $room->floor,
                        'notes' => $notes,
                    ];
                }
            }

            return [
                'allocated' => $allocated,
                'waiting' => $waiting,
                'incomplete' => $incomplete
                    ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
                    ->values()
                    ->all(),
            ];
        }, 3);
    }

    /**
     * Penghuni yang belum punya slot. Yang pernah check-out boleh dialokasikan lagi
     * (keputusan pengelola no. 9).
     *
     * @param  Collection<int, User>  $users
     * @return Collection<int, User>
     */
    private function pendingResidents(Collection $users, RoomOccupancy $occupancy): Collection
    {
        return $users->filter(
            fn (User $user) => $user->role === Role::Resident && ! $occupancy->isAssigned($user)
        );
    }

    private function homeFloorFor(string $cohort, RoomOccupancy $occupancy): ?int
    {
        $floors = config('asrama.floors');
        $activeRooms = $occupancy->rooms()->filter(fn (Room $room) => $room->is_active);

        $existingFloors = $occupancy->floorsOfCohort($cohort);

        // Cadangkan satu lantai kosong utuh untuk cohort baru bila masih tersedia.
        $unusedFloors = collect($floors)->filter(
            fn (int $floor) => $activeRooms->where('floor', $floor)->isNotEmpty()
                && ! $activeRooms->where('floor', $floor)->contains(
                    fn (Room $room) => count($occupancy->occupantsOf($room)) > 0
                )
        );

        $availableFloors = $activeRooms
            ->filter(fn (Room $room) => $occupancy->hasFreeSlot($room))
            ->pluck('floor');

        return collect($existingFloors)->max() ?? $unusedFloors->first() ?? $availableFloors->max();
    }

    /**
     * Mencari kamar bertahap (keputusan pengelola no. 2, 3, 4). Jenis kelamin dan kapasitas
     * selalu wajib cocok; yang dilonggarkan bertahap adalah agama, lalu lantai:
     *  1. lantai asal ke bawah, seagama; 2. lantai asal ke bawah, agama dilonggarkan;
     *  3. lantai di atas lantai asal (terdekat dulu), seagama; 4. sama, agama dilonggarkan.
     */
    private function findRoomFor(User $resident, ?int $homeFloor, RoomOccupancy $occupancy): ?Room
    {
        if ($homeFloor === null) {
            return null;
        }

        $floors = config('asrama.floors');
        $groups = [
            array_values(array_filter($floors, fn (int $floor) => $floor <= $homeFloor)),
            array_values(array_filter(array_reverse($floors), fn (int $floor) => $floor > $homeFloor)),
        ];

        foreach ($groups as $group) {
            foreach ([false, true] as $tolerateReligion) {
                $room = $this->findInFloors(
                    $resident,
                    $this->preferSameGenderFloors($group, $resident, $occupancy),
                    $tolerateReligion,
                    $occupancy
                );

                if ($room !== null) {
                    return $room;
                }
            }
        }

        return null;
    }

    /**
     * Lantai tanpa penghuni berjenis kelamin lain didahulukan (pengelompokan lantai per jenis
     * kelamin bersifat pilihan, bukan syarat); urutan semula dipertahankan di dalam tiap kelompok.
     *
     * @param  list<int>  $floors
     * @return list<int>
     */
    private function preferSameGenderFloors(array $floors, User $resident, RoomOccupancy $occupancy): array
    {
        $mixed = fn (int $floor) => $occupancy->rooms()->where('floor', $floor)->contains(
            fn (Room $room) => collect($occupancy->occupantsOf($room))
                ->contains(fn (User $person) => $person->gender !== $resident->gender)
        );

        return [
            ...array_filter($floors, fn (int $floor) => ! $mixed($floor)),
            ...array_filter($floors, $mixed),
        ];
    }

    /** @param  list<int>  $floors */
    private function findInFloors(User $resident, array $floors, bool $tolerateReligion, RoomOccupancy $occupancy): ?Room
    {
        foreach ($floors as $floor) {
            $selected = $occupancy->rooms()
                ->where('floor', $floor)
                ->filter(fn (Room $room) => $this->canHouse($room, $resident, $tolerateReligion, $occupancy))
                ->sortBy(fn (Room $room) => [$this->fillPriority($room, $resident, $occupancy), $room->number])
                ->first();

            if ($selected !== null) {
                return $selected;
            }
        }

        return null;
    }

    private function canHouse(Room $room, User $resident, bool $tolerateReligion, RoomOccupancy $occupancy): bool
    {
        return $room->is_active
            && $occupancy->hasFreeSlot($room)
            && collect($occupancy->occupantsOf($room))->every(
                fn (User $person) => $person->gender === $resident->gender
                    && ($tolerateReligion || $person->religion === $resident->religion)
            );
    }

    /** Isi kamar terisi sebagian yang cocok sebelum membuka kamar kosong. */
    private function fillPriority(Room $room, User $resident, RoomOccupancy $occupancy): int
    {
        $people = $occupancy->occupantsOf($room);

        if ($people === []) {
            return self::PRIORITY_EMPTY_ROOM;
        }

        return $people[0]->cohort === $resident->cohort
            ? self::PRIORITY_SAME_COHORT
            : self::PRIORITY_OTHER_COHORT;
    }

    private function hasCompleteProfile(User $user): bool
    {
        return $user->cohort !== ''
            && $user->gender !== null
            && $user->religion !== null;
    }
}
