<?php

namespace App\Support;

use App\Models\Room;
use App\Models\RoomSlot;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

/**
 * Peta penghuni dan slot kosong per kamar selama satu proses alokasi otomatis.
 * Data diambil dari baris yang sudah dikunci oleh RoomLocks.
 */
class RoomOccupancy
{
    /** @var array<int, list<User>> */
    private array $occupants = [];

    /** @var array<int, int> */
    private array $freeSlots = [];

    /** @var array<int, true> */
    private array $assignedUserIds = [];

    /**
     * @param  Collection<int, Room>  $rooms
     * @param  Collection<int, RoomSlot>  $slots
     * @param  \Illuminate\Support\Collection<int, User>  $usersById
     */
    public function __construct(
        private readonly Collection $rooms,
        private readonly Collection $slots,
        \Illuminate\Support\Collection $usersById,
    ) {
        foreach ($rooms as $room) {
            $this->freeSlots[$room->id] = 0;
        }

        foreach ($slots as $slot) {
            if ($slot->user_id !== null && isset($usersById[$slot->user_id])) {
                $this->occupants[$slot->room_id][] = $usersById[$slot->user_id];
                $this->assignedUserIds[$slot->user_id] = true;
            } elseif ($slot->user_id === null && isset($this->freeSlots[$slot->room_id])) {
                $this->freeSlots[$slot->room_id]++;
            }
        }
    }

    /** @return Collection<int, Room> */
    public function rooms(): Collection
    {
        return $this->rooms;
    }

    /** @return list<User> */
    public function occupantsOf(Room $room): array
    {
        return $this->occupants[$room->id] ?? [];
    }

    public function hasFreeSlot(Room $room): bool
    {
        return ($this->freeSlots[$room->id] ?? 0) > 0;
    }

    public function isAssigned(User $user): bool
    {
        return isset($this->assignedUserIds[$user->id]);
    }

    /** Lantai-lantai tempat anggota angkatan tersebut sudah ditempatkan. */
    public function floorsOfCohort(string $cohort, ?int $exceptUserId = null): array
    {
        $floors = [];

        foreach ($this->rooms as $room) {
            foreach ($this->occupantsOf($room) as $occupant) {
                if ($occupant->id !== $exceptUserId && User::normalizeProfileText($occupant->cohort) === $cohort) {
                    $floors[$room->floor] = $room->floor;
                }
            }
        }

        return array_values($floors);
    }

    /** Melepas slot penghuni; mengembalikan kamar asalnya, atau null bila tidak punya slot. */
    public function unassign(User $resident): ?Room
    {
        $slot = $this->slots->first(fn (RoomSlot $slot) => $slot->user_id === $resident->id);

        if ($slot === null) {
            return null;
        }

        $slot->update(['user_id' => null, 'checked_in_at' => null]);
        $this->occupants[$slot->room_id] = array_values(array_filter(
            $this->occupants[$slot->room_id] ?? [],
            fn (User $occupant) => $occupant->id !== $resident->id
        ));
        unset($this->assignedUserIds[$resident->id]);
        $this->freeSlots[$slot->room_id]++;

        return $this->rooms->firstWhere('id', $slot->room_id);
    }

    public function assign(User $resident, Room $room): void
    {
        $slot = $this->slots->first(
            fn (RoomSlot $slot) => $slot->room_id === $room->id && $slot->user_id === null
        );

        if ($slot === null) {
            throw new RuntimeException('Slot kamar tidak konsisten.');
        }

        $slot->update(['user_id' => $resident->id, 'checked_in_at' => null]);
        $this->occupants[$room->id][] = $resident;
        $this->assignedUserIds[$resident->id] = true;
        $this->freeSlots[$room->id] = max(0, $this->freeSlots[$room->id] - 1);
    }
}
