<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'role' => Role::Resident,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => Role::Admin,
        ]);
    }

    public function resident(): static
    {
        return $this->state(fn () => [
            'role' => Role::Resident,
        ]);
    }

    public function profiled(
        string $cohort = '64',
        Gender $gender = Gender::Male,
        string $religion = 'islam'
    ): static {
        return $this->state(fn () => [
            'cohort' => $cohort,
            'gender' => $gender,
            'religion' => $religion,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }
}
