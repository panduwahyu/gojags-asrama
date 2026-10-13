<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.id'],
            [
                'name' => 'Admin Gojags',
                'password' => 'admin123',
                'role' => Role::Admin,
            ]
        );

        User::firstOrCreate(
            ['email' => 'user@example.id'],
            [
                'name' => 'Pandu Wahyu Aji',
                'password' => 'user123',
                'role' => Role::Resident,
            ]
        );

        for ($number = 1; $number <= 10; $number++) {
            User::firstOrCreate(
                ['email' => "user{$number}@example.id"],
                [
                    'name' => "User {$number}",
                    'password' => 'user123',
                    'role' => Role::Resident,
                    'cohort' => $number <= 5 ? '64' : '63',
                    'gender' => $number <= 5 ? Gender::Male : Gender::Female,
                    'religion' => null,
                ]
            );
        }
    }
}
