<?php

namespace Database\Factories;

use App\Enums\MealTime;
use App\Models\CateringMenu;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<CateringMenu>
 */
class CateringMenuFactory extends Factory
{
    public function definition(): array
    {
        [$servedAt, $mealTime] = $this->nextFreeSlot();

        return [
            'menu_number' => fake()->numberBetween(1, 100),
            'dish_name' => fake()->words(3, true),
            'meal_time' => $mealTime,
            'provider' => fake()->company(),
            'served_at' => $servedAt,
        ];
    }

    /**
     * Pasangan (tanggal hidang, waktu makan) berikutnya yang belum terpakai, mulai dari hari ini,
     * agar kunci unik UNIQUE(served_on, meal_time) tidak bertabrakan di tes.
     *
     * @return array{0: Carbon, 1: MealTime}
     */
    private function nextFreeSlot(): array
    {
        for ($daysAgo = 0; ; $daysAgo++) {
            $servedAt = now()->subDays($daysAgo);
            $servedOn = CateringMenu::servedOnFor($servedAt);

            foreach (MealTime::cases() as $mealTime) {
                $taken = CateringMenu::where('served_on', $servedOn)->where('meal_time', $mealTime)->exists();

                if (! $taken) {
                    return [$servedAt, $mealTime];
                }
            }
        }
    }
}
