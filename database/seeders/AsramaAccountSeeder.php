<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AsramaAccountSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['email' => 'admin@example.id', 'name' => 'Admin Gojags', 'password' => 'admin123', 'role' => 'admin'],
            ['email' => 'user@example.id', 'name' => 'Pandu Wahyu Aji', 'password' => 'user123', 'role' => 'user'],
        ] as $account) {
            User::updateOrCreate(['email' => $account['email']], $account);
        }
    }
}
