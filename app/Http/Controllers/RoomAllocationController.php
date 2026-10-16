<?php

namespace App\Http\Controllers;

use App\Services\RoomAllocator;
use App\Services\TrainingSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoomAllocationController extends Controller
{
    public function allocate(Request $request, RoomAllocator $allocator)
    {
        $data = $request->validate(['batch_id' => ['required', 'integer', 'exists:training_batches,id']]);
        $result = $allocator->allocate((int) $data['batch_id']);
        $message = count($result['allocated']).' user dialokasikan. '.count($result['incomplete']).' profil belum lengkap. '.count($result['waiting']).' user menunggu kamar yang sesuai.';
        return $request->expectsJson()
            ? response()->json(['message' => $message] + $result)
            : redirect('/kamar')->with('success', $message);
    }

    public function sync(Request $request, TrainingSync $sync)
    {
        $result = $sync->sync();
        $message = $result['batches'].' batch dan '.$result['participants'].' peserta berhasil disinkronkan.';
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'batches' => TrainingSync::batches()])
            : redirect('/kamar')->with('success', $message);
    }

    public function profile(Request $request, int $id)
    {
        $data = $request->validate([
            'angkatan' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
            'agama' => ['required', 'string', 'max:50'],
        ]);
        $data = array_map(fn ($value) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value))), $data);
        $data['gender'] = $request->input('gender');
        if ($data['angkatan'] === '' || $data['agama'] === '') {
            throw ValidationException::withMessages(['profile' => 'Angkatan dan agama wajib diisi.']);
        }
        DB::transaction(function () use ($id, $data) {
            DB::table('kamar')->orderByDesc('lantai')->orderBy('name')->lockForUpdate()->get();
            $user = DB::table('users')->where('id', $id)->lockForUpdate()->first();
            abort_unless($user && $user->role === 'user', 404);
            if (DB::table('kamar_slots')->where('user_id', $id)->exists()) {
                throw ValidationException::withMessages(['profile' => 'Profil penghuni yang sudah dialokasikan tidak dapat diubah.']);
            }
            DB::table('users')->where('id', $id)->update($data + ['updated_at' => now()]);
        }, 3);
        return $request->expectsJson()
            ? response()->json(['message' => 'Profil alokasi berhasil disimpan.'])
            : redirect('/kamar')->with('success', 'Profil alokasi berhasil disimpan.');
    }
}
