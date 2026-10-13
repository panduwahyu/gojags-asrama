<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('asrama:admin', function () {
    $data = [
        'name' => $this->ask('Nama pengelola'),
        'email' => $this->ask('Email pengelola'),
        'password' => $this->secret('Kata sandi (minimal 12 karakter)'),
    ];
    $validator = Validator::make($data, [
        'name' => 'required|string|max:100',
        'email' => 'required|email|max:255|unique:users,email',
        'password' => 'required|string|min:12',
    ]);
    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }
    User::create($data + ['role' => 'admin']);
    $this->info('Akun pengelola berhasil dibuat. Silakan login.');
})->purpose('Membuat akun pengelola Gojags Asrama');
