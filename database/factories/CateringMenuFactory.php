<?php

namespace Database\Factories;

use App\Enums\CateringStatus;
use App\Enums\MealTime;
use App\Models\CateringMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CateringMenu>
 */
class CateringMenuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'menu_number' => fake()->numberBetween(1, 100),
            'dish_name' => fake()->words(3, true),
            'meal_time' => fake()->randomElement(MealTime::cases()),
            'provider' => fake()->company(),
            'served_at' => now(),
            'status' => CateringStatus::Scheduled,
        ];
    }
}
