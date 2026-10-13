<?php

namespace Database\Factories;

use App\Enums\ComplaintStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Complaint>
 */
class ComplaintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'status' => ComplaintStatus::Pending,
            'resolved_by' => null,
            'resolved_at' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => ComplaintStatus::Resolved,
            'resolved_by' => User::factory()->admin(),
            'resolved_at' => now(),
        ]);
    }
}
