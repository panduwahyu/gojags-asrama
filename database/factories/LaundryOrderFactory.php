<?php

namespace Database\Factories;

use App\Enums\LaundryStatus;
use App\Models\LaundryOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LaundryOrder>
 */
class LaundryOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'items' => fake()->words(3, true),
            'set_count' => fake()->numberBetween(1, 5),
            'status' => LaundryStatus::Pending,
            'cost' => null,
        ];
    }

    public function done(string $cost = '15000.00'): static
    {
        return $this->state(fn () => [
            'status' => LaundryStatus::Done,
            'cost' => $cost,
        ]);
    }
}
