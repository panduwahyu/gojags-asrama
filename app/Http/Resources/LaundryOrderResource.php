<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaundryOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_name' => $this->user->name,
            'items' => $this->items,
            'set_count' => $this->set_count,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'cost' => $this->cost,
            'created_at' => $this->created_at,
        ];
    }
}
