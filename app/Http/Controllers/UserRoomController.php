<?php

namespace App\Http\Controllers;

use App\Services\RoomStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserRoomController extends Controller
{
    private function iso(?string $time): ?string
    {
        return $time ? Carbon::parse($time, config('app.timezone'))->toISOString() : null;
    }

    public function show(Request $request)
    {
        $slot = DB::table('kamar_slots')->join('kamar', 'kamar.id', '=', 'kamar_slots.kamar_id')
            ->where('user_id', $request->user()->id)->first(['kamar.name', 'kamar.lantai', 'kamar_slots.checked_in_at']);
        $history = DB::table('kamar_stays')->where('user_id', $request->user()->id)->orderByDesc('id')->get()
            ->map(fn ($stay) => [
                'id' => $stay->id, 'kamar' => $stay->kamar_name, 'lantai' => $stay->lantai,
                'checked_in_at' => $this->iso($stay->checked_in_at), 'checked_out_at' => $this->iso($stay->checked_out_at),
            ]);
        $data = [
            'page' => 'user-kamar',
            'room' => $slot ? ['name' => $slot->name, 'lantai' => $slot->lantai, 'checked_in_at' => $this->iso($slot->checked_in_at)] : null,
            'history' => $history,
            'leave_permissions' => $request->expectsJson() ? [] : LeavePermissionController::query($request)->orderByDesc('izin_keluar.id')->get(),
            'complaints' => $request->expectsJson() ? [] : ComplaintController::query($request)->orderByDesc('keluhan_asrama.id')->get(),
        ];
        return $request->expectsJson() ? response()->json($data)->header('Cache-Control', 'no-store') : view('asrama', $data);
    }

    public function service(string $service)
    {
        abort_unless(in_array($service, ['laundry', 'catering']), 404);
        return view('asrama', ['page' => 'user-'.$service]);
    }

    public function checkIn(Request $request)
    {
        return $this->transition($request, false);
    }

    public function checkOut(Request $request)
    {
        return $this->transition($request, true);
    }

    private function transition(Request $request, bool $checkout)
    {
        DB::transaction(function () use ($request, $checkout) {
            // Match the allocator/profile lock order to serialize occupancy changes.
            DB::table('kamar')->orderByDesc('lantai')->orderBy('name')->lockForUpdate()->get();
            $user = DB::table('users')->where('id', $request->user()->id)->lockForUpdate()->first();
            $slot = DB::table('kamar_slots')->where('user_id', $user->id)->lockForUpdate()->first();
            if (!$slot) throw ValidationException::withMessages(['room' => 'Anda belum memiliki alokasi kamar.']);
            $room = DB::table('kamar')->find($slot->kamar_id);
            $time = now();
            if ($checkout) {
                if (!$slot->checked_in_at) throw ValidationException::withMessages(['room' => 'Anda harus check-in sebelum check-out.']);
                $stay = DB::table('kamar_stays')->where('user_id', $user->id)->where('kamar_id', $room->id)->whereNull('checked_out_at')->lockForUpdate()->first();
                if (!$stay) throw ValidationException::withMessages(['room' => 'Riwayat check-in aktif tidak ditemukan.']);
                DB::table('kamar_stays')->where('id', $stay->id)->update(['checked_out_at' => $time, 'updated_at' => $time]);
                DB::table('kamar_slots')->where('id', $slot->id)->update(['user_id' => null, 'checked_in_at' => null, 'updated_at' => $time]);
            } else {
                if ($slot->checked_in_at) throw ValidationException::withMessages(['room' => 'Anda sudah check-in.']);
                DB::table('kamar_stays')->insert([
                    'user_id' => $user->id, 'kamar_id' => $room->id, 'user_name' => $user->name,
                    'kamar_name' => $room->name, 'lantai' => $room->lantai, 'checked_in_at' => $time,
                    'created_at' => $time, 'updated_at' => $time,
                ]);
                DB::table('kamar_slots')->where('id', $slot->id)->update(['checked_in_at' => $time, 'updated_at' => $time]);
            }
            RoomStatus::sync($room->id);
        }, 3);
        $message = $checkout ? 'Check-out berhasil. Slot kamar telah dilepaskan.' : 'Check-in berhasil.';
        return $request->expectsJson() ? response()->json(['message' => $message]) : redirect('/user/kamar')->with('success', $message);
    }
}
