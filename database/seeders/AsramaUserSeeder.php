<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AsramaUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            for ($number = 1; $number <= 10; $number++) {
                $email = 'user'.$number.'@example.id';

                // Re-running this seeder leaves existing passwords and profiles intact.
                if (User::where('email', $email)->exists()) {
                    continue;
                }

                $user = new User;
                $user->forceFill([
                    'name' => 'User '.$number,
                    'email' => $email,
                    'password' => 'user123',
                    'role' => 'user',
                    'gender' => $number <= 5 ? 'Laki-laki' : 'Perempuan',
                    'angkatan' => $number <= 5 ? '64' : '63',
                    'agama' => null,
                ])->save();
            }
        });
    }
}
