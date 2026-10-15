<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\Room;
use App\Models\Stay;
use App\Models\User;
use App\Support\RoomLocks;
use App\Support\RoomOccupancy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AllocateRooms
{
    /**
     * @return array{
     *     allocated: list<array{id: int, name: string, room: string, floor: int}>,
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
                $user->cohort = $this->normalize($user->cohort);
                $user->religion = $this->normalize($user->religion);
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

                    $occupancy->assign($resident, $room);
                    $allocated[] = [
                        'id' => $resident->id,
                        'name' => $resident->name,
                        'room' => $room->number,
                        'floor' => $room->floor,
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
     * @param  Collection<int, User>  $users
     * @return Collection<int, User>
     */
    private function pendingResidents(Collection $users, RoomOccupancy $occupancy): Collection
    {
        $departed = Stay::query()
            ->whereNotNull('checked_out_at')
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->flip()
            ->all();

        return $users->filter(
            fn (User $user) => $user->role === Role::Resident
                && ! $occupancy->isAssigned($user)
                && ! isset($departed[$user->id])
        );
    }

    private function homeFloorFor(string $cohort, RoomOccupancy $occupancy): ?int
    {
        $floors = config('asrama.floors');
        $rooms = $occupancy->rooms();

        $existingFloors = $rooms
            ->filter(
                fn (Room $room) => collect($occupancy->occupantsOf($room))
                    ->contains(fn (User $user) => $user->cohort === $cohort)
            )
            ->pluck('floor');

        // Cadangkan satu lantai kosong utuh untuk cohort baru bila masih tersedia.
        $unusedFloors = collect($floors)->filter(
            fn (int $floor) => ! $rooms
                ->where('floor', $floor)
                ->contains(fn (Room $room) => count($occupancy->occupantsOf($room)) > 0)
        );

        $availableFloors = $rooms
            ->filter(fn (Room $room) => $occupancy->hasFreeSlot($room))
            ->pluck('floor');

        return $existingFloors->max() ?? $unusedFloors->first() ?? $availableFloors->max();
    }

    private function findRoomFor(User $resident, ?int $homeFloor, RoomOccupancy $occupancy): ?Room
    {
        if ($homeFloor === null) {
            return null;
        }

        foreach (config('asrama.floors') as $floor) {
            if ($floor > $homeFloor) {
                continue;
            }

            $candidates = $occupancy->rooms()->where('floor', $floor)->filter(
                function (Room $room) use ($occupancy, $resident) {
                    $people = $occupancy->occupantsOf($room);

                    return $occupancy->hasFreeSlot($room)
                        && collect($people)->every(
                            fn (User $person) => $person->gender === $resident->gender
                                && $person->religion === $resident->religion
                        );
                }
            );

            // Isi kamar terisi sebagian yang cocok sebelum membuka kamar kosong.
            $selected = $candidates->sortBy(function (Room $room) use ($occupancy, $resident) {
                $people = $occupancy->occupantsOf($room);
                $priority = ! $people
                    ? 2
                    : ($people[0]->cohort === $resident->cohort ? 0 : 1);

                return [$priority, $room->number];
            })->first();

            if ($selected !== null) {
                return $selected;
            }
        }

        return null;
    }

    private function hasCompleteProfile(User $user): bool
    {
        return $user->cohort !== ''
            && $user->gender !== null
            && $user->religion !== '';
    }

    private function normalize(?string $value): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $value ?? '')));
    }
}
