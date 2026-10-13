<?php

namespace App\Http\Controllers;

use App\Enums\PermitStatus;
use App\Http\Requests\ReviewLeavePermitRequest;
use App\Http\Requests\SaveLeavePermitRequest;
use App\Http\Resources\LeavePermitResource;
use App\Models\LeavePermit;
use App\Models\User;
use App\Support\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeavePermitController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $permits = TableQuery::for(LeavePermit::visibleTo($request->user())->with('user'))
            ->search(['reason', 'status', 'user.name'])
            ->sort([
                'leaves_at',
                'returns_at',
                'reason',
                'status',
                'user_name' => User::nameOf('leave_permits'),
            ])
            ->paginate($request);

        return LeavePermitResource::collection($permits);
    }

    public function store(SaveLeavePermitRequest $request): JsonResponse
    {
        $request->user()->leavePermits()->create($request->validated());

        return $this->message('Izin keluar berhasil diajukan.', 201);
    }

    public function update(SaveLeavePermitRequest $request, LeavePermit $permit): JsonResponse
    {
        DB::transaction(function () use ($request, $permit) {
            // Mengunci baris agar tidak bertabrakan dengan keputusan admin; milik orang lain -> 404.
            $locked = $request->user()->leavePermits()->lockForUpdate()->findOrFail($permit->id);
            $this->ensurePending($locked, 'Hanya izin berstatus Diajukan yang dapat diedit.');
            $locked->update($request->validated());
        }, 3);

        return $this->message('Pengajuan izin berhasil diperbarui.');
    }

    public function review(ReviewLeavePermitRequest $request, LeavePermit $permit): JsonResponse
    {
        $status = PermitStatus::from($request->validated('status'));

        DB::transaction(function () use ($request, $permit, $status) {
            $locked = LeavePermit::lockForUpdate()->findOrFail($permit->id);
            $this->ensurePending($locked, 'Pengajuan ini sudah diproses.');
            $locked->forceFill([
                'status' => $status,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ])->save();
        }, 3);

        return $this->message('Izin keluar '.mb_strtolower($status->label()).'.');
    }

    private function ensurePending(LeavePermit $permit, string $message): void
    {
        if (! $permit->isPending()) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }
}
