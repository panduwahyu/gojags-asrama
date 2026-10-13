<?php

namespace App\Http\Resources;

use App\Models\RoomSlot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $residents = $this->slots
            ->filter(fn (RoomSlot $slot) => $slot->user !== null)
            ->sortBy(fn (RoomSlot $slot) => $slot->user->name)
            ->map(fn (RoomSlot $slot) => [
                'id' => $slot->user->id,
                'name' => $slot->user->name,
                'checked_in_at' => $slot->checked_in_at,
            ])
            ->values()
            ->all();

        return [
            'id' => $this->id,
            'number' => $this->number,
            'floor' => $this->floor,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'assigned_count' => (int) $this->assigned_count,
            'capacity' => (int) config('asrama.room_capacity'),
            'residents' => $residents,
        ];
    }
}
