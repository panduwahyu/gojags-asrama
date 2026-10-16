<?php

namespace App\Http\Controllers;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoomQrController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:training_batches,id'],
            'action' => ['required', Rule::in(['check-in', 'check-out'])],
        ]);
        $token = bin2hex(random_bytes(32));
        $payload = 'gojags-room:v1:'.$token;
        $expires = now()->addMinutes(15);
        // Render before changing the session so a rendering failure preserves the old QR.
        $svg = (new Writer(new ImageRenderer(new RendererStyle(320, 24), new SvgImageBackEnd())))->writeString($payload);
        $batch = DB::transaction(function () use ($request, $data, $token, $expires) {
            $batch = DB::table('training_batches')->where('id', $data['batch_id'])->lockForUpdate()->first();
            if (!$batch || !DB::table('training_participants')->where('batch_id', $batch->id)->where('active', true)->exists()) {
                throw ValidationException::withMessages(['batch_id' => 'Batch tidak memiliki peserta aktif. Sinkronkan peserta terlebih dahulu.']);
            }
            DB::table('room_qr_sessions')->where('batch_id', $batch->id)->where('action', $data['action'])->whereNull('revoked_at')->update(['revoked_at' => now(), 'updated_at' => now()]);
            DB::table('room_qr_sessions')->insert([
                'batch_id' => $batch->id, 'created_by' => $request->user()->id, 'action' => $data['action'],
                'token_hash' => hash('sha256', $token), 'expires_at' => $expires,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            return $batch;
        }, 3);
        $result = [
            'message' => 'QR '.$data['action'].' berhasil dibuat. Berlaku 15 menit.',
            'batch_id' => $batch->id, 'batch_name' => $batch->name, 'action' => $data['action'],
            'qr_payload' => $payload, 'expires_at' => $expires->toISOString(), 'qr_image' => 'data:image/svg+xml;base64,'.base64_encode($svg),
        ];
        return $request->expectsJson()
            ? response()->json($result)->header('Cache-Control', 'no-store')
            : response()->view('room-qr', $result)->header('Cache-Control', 'no-store');
    }
}
