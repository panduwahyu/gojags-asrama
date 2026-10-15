<?php

namespace App\Http\Controllers;

use App\Enums\PermitStatus;
use App\Http\Requests\ExportRequest;
use App\Http\Requests\ReviewLeavePermitRequest;
use App\Http\Requests\SaveLeavePermitRequest;
use App\Http\Resources\LeavePermitResource;
use App\Models\LeavePermit;
use App\Models\User;
use App\Support\TableExport;
use App\Support\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeavePermitController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $permits = $this->tableQuery($request)->paginate($request);

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

            if ($locked->status === PermitStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'status' => 'Izin yang sudah dibatalkan tidak dapat ditinjau.',
                ]);
            }

            // Petugas boleh mengubah keputusan (mis. disetujui menjadi ditolak).
            $locked->forceFill([
                'status' => $status,
                'rejection_reason' => $status === PermitStatus::Rejected ? $request->validated('rejection_reason') : null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ])->save();
        }, 3);

        return $this->message('Izin keluar '.mb_strtolower($status->label()).'.');
    }

    public function cancel(Request $request, LeavePermit $permit): JsonResponse
    {
        DB::transaction(function () use ($request, $permit) {
            // Milik orang lain -> 404.
            $locked = $request->user()->leavePermits()->lockForUpdate()->findOrFail($permit->id);
            $this->ensurePending($locked, 'Hanya izin berstatus Diajukan yang dapat dibatalkan.');
            $locked->forceFill(['status' => PermitStatus::Cancelled])->save();
        }, 3);

        return $this->message('Pengajuan izin dibatalkan.');
    }

    private function ensurePending(LeavePermit $permit, string $message): void
    {
        if (! $permit->isPending()) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }

    private function tableQuery(Request $request): TableQuery
    {
        return TableQuery::for(LeavePermit::visibleTo($request->user())->with('user'))
            ->search(['reason', 'user.name'])
            ->status('status', PermitStatus::class)
            ->sort([
                'leaves_at',
                'returns_at',
                'reason',
                'user_name' => User::nameOf('leave_permits'),
            ]);
    }

    public function export(ExportRequest $request): StreamedResponse
    {
        $rows = $this->tableQuery($request)->export($request)->map(fn (LeavePermit $permit) => [
            $permit->user->name,
            TableExport::wib($permit->leaves_at),
            TableExport::wib($permit->returns_at),
            $permit->reason,
            $permit->status->label(),
            $permit->rejection_reason,
        ]);

        return TableExport::download($request->validated('format'), 'Izin keluar',
            ['Nama', 'Waktu keluar', 'Waktu kembali', 'Alasan', 'Status', 'Alasan penolakan'], $rows->all());
    }
}
