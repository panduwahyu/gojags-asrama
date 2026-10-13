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
            'cohort' => $this->cohort,
            'gender' => $this->gender?->value,
            'religion' => $this->religion,
            'room_id' => $this->slot?->room_id,
        ];
    }
}
