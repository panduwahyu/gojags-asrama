<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrainingSync
{
    public function sync(): array
    {
        $url = rtrim((string) config('services.api_gojags.url'), '/');
        $token = (string) config('services.api_gojags.token');
        if (!$url || !$token) {
            throw ValidationException::withMessages(['sync' => 'Atur API_GOJAGS_URL dan API_GOJAGS_TOKEN pada backend terlebih dahulu.']);
        }
        try {
            $response = Http::acceptJson()->withToken($token)->connectTimeout(5)->timeout(20)->get($url.'/api/batches');
            if (!$response->successful()) throw new \RuntimeException('API tidak berhasil.');
            $payload = $response->json();
            if (!is_array($payload)) throw new \RuntimeException('Respons tidak valid.');
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['sync' => 'Sinkronisasi gagal. Periksa koneksi dan token api-gojags, lalu coba lagi.']);
        }
        $data = Validator::make($payload, [
            'data' => ['present', 'array', 'max:100'],
            'data.*.id' => ['required', 'string', 'max:100', 'distinct:strict'],
            'data.*.name' => ['required', 'string', 'max:255'],
            'data.*.participants' => ['present', 'array', 'max:1000'],
            'data.*.participants.*.id' => ['required', 'string', 'max:100', 'distinct:strict'],
            'data.*.participants.*.name' => ['required', 'string', 'max:255'],
            'data.*.participants.*.email' => ['required', 'email', 'max:255', 'distinct:ignore_case'],
            'data.*.participants.*.angkatan' => ['required', 'string', 'max:100'],
            'data.*.participants.*.gender' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
            'data.*.participants.*.agama' => ['required', 'string', 'max:50'],
        ])->validate()['data'];

        return DB::transaction(function () use ($data) {
            // Match allocation/check-in lock order; never change occupied profiles.
            DB::table('kamar')->orderByDesc('lantai')->orderBy('name')->lockForUpdate()->get();
            DB::table('users')->orderBy('id')->lockForUpdate()->get();
            DB::table('training_participants')->update(['active' => false]);
            $total = 0;
            foreach ($data as $batch) {
                DB::table('training_batches')->updateOrInsert(['source_id' => $batch['id']], ['name' => trim($batch['name']), 'synced_at' => now(), 'updated_at' => now()]);
                $batchId = DB::table('training_batches')->where('source_id', $batch['id'])->value('id');
                foreach ($batch['participants'] as $person) {
                    $mapping = DB::table('training_participants')->where('source_id', $person['id'])->first();
                    $profile = [
                        'name' => trim($person['name']), 'email' => mb_strtolower(trim($person['email'])),
                        'angkatan' => trim($person['angkatan']), 'gender' => $person['gender'], 'agama' => trim($person['agama']),
                    ];
                    if (in_array('', $profile, true)) throw ValidationException::withMessages(['sync' => 'Data profil peserta tidak boleh kosong.']);
                    $conflict = DB::table('users')->where('email', $profile['email'])->when($mapping, fn ($query) => $query->where('id', '!=', $mapping->user_id))->exists();
                    if ($conflict) throw ValidationException::withMessages(['sync' => 'Email peserta sudah digunakan akun lain. Data tidak diubah.']);
                    if ($mapping) {
                        $user = DB::table('users')->find($mapping->user_id);
                        if ($user->role !== 'user') throw ValidationException::withMessages(['sync' => 'Akun peserta tidak memiliki role user.']);
                        if (DB::table('kamar_slots')->where('user_id', $user->id)->exists()) {
                            $changed = (int) $mapping->batch_id !== (int) $batchId;
                            foreach ($profile as $key => $value) $changed = $changed || mb_strtolower(trim($user->$key)) !== mb_strtolower($value);
                            if ($changed) throw ValidationException::withMessages(['sync' => 'Profil peserta yang masih menempati kamar berubah di API. Selesaikan alokasi sebelumnya sebelum sinkronisasi.']);
                        } else {
                            DB::table('users')->where('id', $user->id)->update($profile + ['updated_at' => now()]);
                        }
                        DB::table('training_participants')->where('id', $mapping->id)->update(['batch_id' => $batchId, 'active' => true, 'updated_at' => now()]);
                    } else {
                        $userId = DB::table('users')->insertGetId($profile + ['role' => 'user', 'password' => Hash::make(Str::random(64)), 'created_at' => now(), 'updated_at' => now()]);
                        DB::table('training_participants')->insert(['source_id' => $person['id'], 'batch_id' => $batchId, 'user_id' => $userId, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
                    }
                    $total++;
                }
            }
            return ['batches' => count($data), 'participants' => $total];
        }, 3);
    }

    public static function batches()
    {
        return DB::table('training_batches')->select('training_batches.id', 'training_batches.name', 'training_batches.synced_at')
            ->selectSub(DB::table('training_participants')->selectRaw('COUNT(*)')->whereColumn('batch_id', 'training_batches.id')->where('active', true), 'participants_count')
            ->orderBy('training_batches.name')->get();
    }
}
