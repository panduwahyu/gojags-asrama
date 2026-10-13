<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CateringMenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'menu_number' => $this->menu_number,
            'provider' => $this->provider,
            'served_at' => $this->served_at,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'rating_average' => $this->rating_average !== null
                ? round((float) $this->rating_average, 2)
                : null,
            'rating_count' => (int) $this->rating_count,
            'my_rating' => $this->my_rating !== null ? (int) $this->my_rating : null,
        ];
    }
}
