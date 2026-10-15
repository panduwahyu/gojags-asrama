<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\Role;
use App\Models\Room;
use App\Models\RoomSlot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
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

    /** Penghuni yang sedang menginap: punya slot dan sudah check-in (beserta riwayat stays aktif). */
    public function staying(): static
    {
        return $this->afterCreating(function (User $user) {
            $room = Room::factory()->create();
            $checkedInAt = now()->subDay();

            RoomSlot::factory()->create([
                'room_id' => $room->id,
                'position' => 1,
                'user_id' => $user->id,
                'checked_in_at' => $checkedInAt,
            ]);
            $user->stays()->create([
                'room_id' => $room->id,
                'user_name' => $user->name,
                'room_number' => $room->number,
                'floor' => $room->floor,
                'checked_in_at' => $checkedInAt,
            ]);
        });
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }
}
