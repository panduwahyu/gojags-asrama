<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RoomAllocator
{
    public function allocate(?int $batchId = null): array
    {
        return DB::transaction(function () use ($batchId) {
            // All allocation/profile requests take these locks in the same order.
            $rooms = DB::table('kamar')->orderByDesc('lantai')->orderBy('name')->lockForUpdate()->get()->keyBy('id');
            $users = DB::table('users')->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($users as $user) {
                foreach (['angkatan', 'gender', 'agama'] as $field) {
                    $user->$field = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $user->$field ?? '')));
                }
            }
            $slots = DB::table('kamar_slots')->orderBy('id')->lockForUpdate()->get();
            $occupants = [];
            $assignedIds = [];
            foreach ($slots as $slot) {
                if ($slot->user_id !== null) {
                    $occupants[$slot->kamar_id][] = $users[$slot->user_id];
                    $assignedIds[$slot->user_id] = true;
                }
            }
            $departed = DB::table('kamar_stays')->whereNotNull('checked_out_at')->pluck('user_id')->flip();
            $batchUsers = $batchId === null ? null : DB::table('training_participants')->where('batch_id', $batchId)->where('active', true)->pluck('user_id')->flip();
            $pending = $users->filter(fn ($user) => $user->role === 'user' && ($batchUsers === null || isset($batchUsers[$user->id])) && !isset($assignedIds[$user->id]) && !isset($departed[$user->id]));
            $incomplete = $pending->filter(fn ($user) => !$user->angkatan || !$user->gender || !$user->agama);
            $groups = $pending->diffKeys($incomplete)->groupBy('angkatan')->sortKeys(SORT_NATURAL);
            $allocated = [];
            $waiting = [];

            foreach ($groups as $cohort => $members) {
                $existingFloors = $rooms->filter(fn ($room) => collect($occupants[$room->id] ?? [])->contains(fn ($user) => $user->angkatan === (string) $cohort))->pluck('lantai');
                // Reserve a wholly unused floor for a new cohort when possible.
                $unusedFloors = collect([5, 4, 3])->filter(fn ($floor) => !$rooms->where('lantai', $floor)->contains(fn ($room) => count($occupants[$room->id] ?? []) > 0));
                $availableFloors = $rooms->filter(fn ($room) => count($occupants[$room->id] ?? []) < 2)->pluck('lantai');
                $homeFloor = $existingFloors->max() ?? $unusedFloors->first() ?? $availableFloors->max();
                foreach ($members as $user) {
                    $selected = null;
                    foreach ([5, 4, 3] as $floor) {
                        if ($homeFloor === null || $floor > $homeFloor) continue;
                        $candidates = $rooms->where('lantai', $floor)->filter(function ($room) use ($occupants, $user) {
                            $people = $occupants[$room->id] ?? [];
                            return count($people) < 2 && collect($people)->every(fn ($person) => $person->gender === $user->gender && $person->agama === $user->agama);
                        });
                        // Fill compatible occupied rooms before opening an empty room.
                        $selected = $candidates->sortBy(function ($room) use ($occupants, $user) {
                            $people = $occupants[$room->id] ?? [];
                            return !$people ? 2 : ($people[0]->angkatan === $user->angkatan ? 0 : 1);
                        })->first();
                        if ($selected) break;
                    }
                    if (!$selected) {
                        $waiting[] = ['id' => $user->id, 'name' => $user->name];
                        continue;
                    }
                    $slot = $slots->first(fn ($slot) => $slot->kamar_id === $selected->id && $slot->user_id === null);
                    if (!$slot) throw new \RuntimeException('Slot kamar tidak konsisten.');
                    DB::table('kamar_slots')->where('id', $slot->id)->update(['user_id' => $user->id, 'updated_at' => now()]);
                    $slot->user_id = $user->id;
                    $occupants[$selected->id][] = $user;
                    $allocated[] = ['id' => $user->id, 'name' => $user->name, 'kamar' => $selected->name, 'lantai' => $selected->lantai];
                }
            }
            foreach ($rooms as $room) {
                RoomStatus::sync($room->id);
            }
            return [
                'allocated' => $allocated, 'waiting' => $waiting,
                'incomplete' => $incomplete->map(fn ($user) => ['id' => $user->id, 'name' => $user->name])->values()->all(),
            ];
        }, 3);
    }
}
