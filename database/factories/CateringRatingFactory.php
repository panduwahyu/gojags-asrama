<?php

namespace Database\Factories;

use App\Models\CateringMenu;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CateringRating>
 */
class CateringRatingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'catering_menu_id' => CateringMenu::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->numberBetween(1, 5),
        ];
    }
}
