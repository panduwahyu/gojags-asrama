<?php

namespace App\Http\Controllers;

use App\Actions\CancelCheckOut;
use App\Actions\CheckOut;
use App\Actions\ImportResidents;
use App\Actions\UnassignRoomSlot;
use App\Actions\UpdateResidentProfile;
use App\Enums\Role;
use App\Http\Requests\ExportRequest;
use App\Http\Requests\ImportResidentsRequest;
use App\Http\Requests\StoreResidentRequest;
use App\Http\Requests\UpdateResidentRequest;
use App\Http\Resources\ResidentResource;
use App\Models\User;
use App\Support\TableExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResidentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ResidentResource::collection($this->residents());
    }

    public function store(StoreResidentRequest $request): JsonResponse
    {
        $password = Str::random(10);
        $resident = User::create([
            ...$request->safe()->except('religion', 'cohort'),
            'cohort' => User::normalizeProfileText($request->validated('cohort')),
            'religion' => $request->validated('religion'),
            'password' => $password,
            'role' => Role::Resident,
        ]);

        return response()->json([
            'message' => 'Akun penghuni berhasil dibuat.',
            'data' => ['id' => $resident->id, 'name' => $resident->name, 'email' => $resident->email, 'password' => $password],
        ], 201);
    }

    public function import(ImportResidentsRequest $request, ImportResidents $action): JsonResponse
    {
        $created = $action->handle($request->file('file'));

        return response()->json([
            'message' => count($created).' akun penghuni berhasil diimpor.',
            'data' => $created,
        ], 201);
    }

    public function update(
        UpdateResidentRequest $request,
        User $resident,
        UpdateResidentProfile $action
    ): JsonResponse {
        $warnings = $action->handle($resident, $request->validated());

        return response()->json([
            'message' => 'Profil alokasi berhasil disimpan.',
            'warnings' => $warnings,
        ]);
    }

    /** Lupa kata sandi ditangani petugas: sandi baru dibuat dan ditampilkan sekali. */
    public function resetPassword(User $resident): JsonResponse
    {
        $this->ensureResident($resident);
        $password = Str::random(10);
        $resident->update(['password' => $password]);

        return response()->json([
            'message' => 'Kata sandi penghuni diatur ulang.',
            'data' => ['id' => $resident->id, 'name' => $resident->name, 'email' => $resident->email, 'password' => $password],
        ]);
    }

    public function checkOut(User $resident, CheckOut $action): JsonResponse
    {
        $this->ensureResident($resident);
        $action->handle($resident);

        return $this->message('Check-out berhasil. Slot kamar telah dilepaskan.');
    }

    public function cancelCheckOut(User $resident, CancelCheckOut $action): JsonResponse
    {
        $action->handle($resident);

        return $this->message('Check-out dibatalkan. Penghuni kembali ke kamarnya.');
    }

    public function unassign(User $resident, UnassignRoomSlot $action): JsonResponse
    {
        $action->handle($resident);

        return $this->message('Alokasi kamar dicabut.');
    }

    public function export(ExportRequest $request): StreamedResponse
    {
        $rows = $this->residents()->map(fn (User $resident) => [
            $resident->name,
            $resident->email,
            $resident->cohort,
            $resident->gender?->label(),
            $resident->religion?->label(),
            $resident->slot?->room?->number,
        ]);

        return TableExport::download($request->validated('format'), 'Penghuni',
            ['Nama', 'Email', 'Angkatan', 'Jenis kelamin', 'Agama', 'Kamar'], $rows->all());
    }

    private function residents()
    {
        return User::residents()->with(['slot.room', 'latestStay'])->orderBy('id')->get();
    }

    private function ensureResident(User $user): void
    {
        abort_unless($user->role === Role::Resident, 404);
    }
}
