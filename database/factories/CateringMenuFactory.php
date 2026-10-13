<?php

namespace Database\Factories;

use App\Enums\CateringStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CateringMenu>
 */
class CateringMenuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'menu_number' => fake()->numberBetween(1, 100),
            'provider' => fake()->company(),
            'served_at' => now()->addHours(2),
            'status' => CateringStatus::Scheduled,
        ];
    }
}
