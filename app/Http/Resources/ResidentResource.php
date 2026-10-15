<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'cohort' => $this->cohort,
            'gender' => $this->gender?->value,
            'religion' => $this->religion?->value,
            'room_id' => $this->slot?->room_id,
            'room_number' => $this->slot?->room?->number,
            'checked_in_at' => $this->slot?->checked_in_at,
            'can_cancel_check_out' => $this->slot === null && $this->latestStay?->checked_out_at !== null,
        ];
    }
}
