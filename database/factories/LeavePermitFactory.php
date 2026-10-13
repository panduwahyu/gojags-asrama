<?php

namespace Database\Factories;

use App\Enums\PermitStatus;
use App\Models\LeavePermit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeavePermit>
 */
class LeavePermitFactory extends Factory
{
    public function definition(): array
    {
        $leavesAt = now()->addDay();

        return [
            'user_id' => User::factory(),
            'leaves_at' => $leavesAt,
            'returns_at' => (clone $leavesAt)->addHours(4),
            'reason' => fake()->sentence(),
            'status' => PermitStatus::Pending,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => PermitStatus::Approved,
            'reviewed_by' => User::factory()->admin(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => PermitStatus::Rejected,
            'reviewed_by' => User::factory()->admin(),
            'reviewed_at' => now(),
        ]);
    }
}
