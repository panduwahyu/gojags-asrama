<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Room>
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
