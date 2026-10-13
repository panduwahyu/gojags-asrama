<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomSlot>
 */
class RoomSlotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'position' => 1,
            'user_id' => null,
            'checked_in_at' => null,
        ];
    }
}
