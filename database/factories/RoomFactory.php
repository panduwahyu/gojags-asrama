<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    public function definition(): array
    {
        $floor = fake()->randomElement([3, 4, 5]);
        $index = fake()->unique()->numberBetween(1, 99);

        return [
            'number' => (string) ($floor * 100 + $index),
            'floor' => $floor,
        ];
    }
}
