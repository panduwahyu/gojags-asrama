<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\Room;
use App\Models\RoomSlot;
use App\Models\Stay;
use App\Models\User;
use App\Support\RoomLocks;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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

            /** @var array<int, list<User>> $occupants */
            $occupants = [];
            $assignedIds = [];
            foreach ($slots as $slot) {
                if ($slot->user_id !== null && isset($usersById[$slot->user_id])) {
                    $occupants[$slot->room_id][] = $usersById[$slot->user_id];
                    $assignedIds[$slot->user_id] = true;
                }
            }

            $pending = $this->pendingResidents($users, $assignedIds);
            $incomplete = $pending->filter(fn (User $user) => ! $this->hasCompleteProfile($user));
            $groups = $pending
                ->diffKeys($incomplete)
                ->groupBy(fn (User $user) => (string) $user->cohort)
                ->sortKeys(SORT_NATURAL);

            $allocated = [];
            $waiting = [];

            foreach ($groups as $cohort => $members) {
                $homeFloor = $this->homeFloorFor((string) $cohort, $rooms, $occupants);

                foreach ($members as $resident) {
                    $room = $this->findRoomFor($resident, $homeFloor, $rooms, $occupants);

                    if ($room === null) {
                        $waiting[] = ['id' => $resident->id, 'name' => $resident->name];

                        continue;
                    }

                    $this->assign($resident, $room, $slots, $occupants);
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
     * @param  array<int, bool>  $assignedIds
     * @return Collection<int, User>
     */
    private function pendingResidents(Collection $users, array $assignedIds): Collection
    {
        $departed = Stay::query()
            ->whereNotNull('checked_out_at')
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->flip()
            ->all();

        return $users->filter(
            fn (User $user) => $user->role === Role::Resident
                && ! isset($assignedIds[$user->id])
                && ! isset($departed[$user->id])
        );
    }

    /**
     * @param  Collection<int, Room>  $rooms
     * @param  array<int, list<User>>  $occupants
     */
    private function homeFloorFor(string $cohort, Collection $rooms, array $occupants): ?int
    {
        $capacity = (int) config('asrama.room_capacity');
        $floors = config('asrama.floors');

        $existingFloors = $rooms
            ->filter(
                fn (Room $room) => collect($occupants[$room->id] ?? [])
                    ->contains(fn (User $user) => $user->cohort === $cohort)
            )
            ->pluck('floor');

        // Cadangkan satu lantai kosong utuh untuk cohort baru bila masih tersedia.
        $unusedFloors = collect($floors)->filter(
            fn (int $floor) => ! $rooms
                ->where('floor', $floor)
                ->contains(fn (Room $room) => count($occupants[$room->id] ?? []) > 0)
        );

        $availableFloors = $rooms
            ->filter(fn (Room $room) => count($occupants[$room->id] ?? []) < $capacity)
            ->pluck('floor');

        return $existingFloors->max() ?? $unusedFloors->first() ?? $availableFloors->max();
    }

    /**
     * @param  Collection<int, Room>  $rooms
     * @param  array<int, list<User>>  $occupants
     */
    private function findRoomFor(
        User $resident,
        ?int $homeFloor,
        Collection $rooms,
        array $occupants
    ): ?Room {
        if ($homeFloor === null) {
            return null;
        }

        $capacity = (int) config('asrama.room_capacity');

        foreach (config('asrama.floors') as $floor) {
            if ($floor > $homeFloor) {
                continue;
            }

            $candidates = $rooms->where('floor', $floor)->filter(
                function (Room $room) use ($occupants, $resident, $capacity) {
                    $people = $occupants[$room->id] ?? [];

                    return count($people) < $capacity
                        && collect($people)->every(
                            fn (User $person) => $person->gender === $resident->gender
                                && $person->religion === $resident->religion
                        );
                }
            );

            // Isi kamar terisi sebagian yang cocok sebelum membuka kamar kosong.
            $selected = $candidates->sortBy(function (Room $room) use ($occupants, $resident) {
                $people = $occupants[$room->id] ?? [];
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

    /**
     * @param  Collection<int, RoomSlot>  $slots
     * @param  array<int, list<User>>  $occupants
     */
    private function assign(
        User $resident,
        Room $room,
        Collection $slots,
        array &$occupants
    ): void {
        $slot = $slots->first(
            fn (RoomSlot $item) => $item->room_id === $room->id && $item->user_id === null
        );

        if ($slot === null) {
            throw new RuntimeException('Slot kamar tidak konsisten.');
        }

        $slot->update(['user_id' => $resident->id]);
        $occupants[$room->id][] = $resident;
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
