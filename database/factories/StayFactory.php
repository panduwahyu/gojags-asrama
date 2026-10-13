<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stay>
 */
class StayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
            'user_name' => fake()->name(),
            'room_number' => '501',
            'floor' => 5,
            'checked_in_at' => now()->subDay(),
            'checked_out_at' => null,
        ];
    }

    public function checkedOut(): static
    {
        return $this->state(fn () => [
            'checked_out_at' => now(),
        ]);
    }
}
