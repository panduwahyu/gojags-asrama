<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintStatus;
use App\Http\Requests\ComplaintResponseRequest;
use App\Http\Requests\ExportRequest;
use App\Http\Requests\StoreComplaintRequest;
use App\Http\Resources\ComplaintResource;
use App\Models\Complaint;
use App\Models\User;
use App\Support\TableExport;
use App\Support\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplaintController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $complaints = $this->tableQuery($request)->paginate($request);

        return ComplaintResource::collection($complaints);
    }

    public function store(StoreComplaintRequest $request): JsonResponse
    {
        $request->user()->complaints()->create($request->validated());

        return $this->message('Keluhan asrama berhasil dikirim.', 201);
    }

    public function resolve(ComplaintResponseRequest $request, Complaint $complaint): JsonResponse
    {
        DB::transaction(function () use ($request, $complaint) {
            $locked = Complaint::lockForUpdate()->findOrFail($complaint->id);

            // Idempoten: keluhan yang sudah Resolved tidak mengubah waktu dan admin penyelesai pertama.
            if (! $locked->isResolved()) {
                $locked->forceFill([
                    'status' => ComplaintStatus::Resolved,
                    'response' => $request->validated('response') ?? $locked->response,
                    'resolved_by' => $request->user()->id,
                    'resolved_at' => now(),
                ])->save();
            }
        }, 3);

        return $this->message('Keluhan ditandai Resolved.');
    }

    public function progress(ComplaintResponseRequest $request, Complaint $complaint): JsonResponse
    {
        DB::transaction(function () use ($request, $complaint) {
            $locked = Complaint::lockForUpdate()->findOrFail($complaint->id);

            if ($locked->isResolved()) {
                throw ValidationException::withMessages([
                    'status' => 'Keluhan yang sudah Resolved harus dibuka kembali sebelum ditangani lagi.',
                ]);
            }

            $locked->forceFill([
                'status' => ComplaintStatus::InProgress,
                'response' => $request->validated('response') ?? $locked->response,
            ])->save();
        }, 3);

        return $this->message('Keluhan ditandai Sedang ditangani.');
    }

    public function reopen(Request $request, Complaint $complaint): JsonResponse
    {
        DB::transaction(function () use ($request, $complaint) {
            // Penghuni hanya dapat membuka kembali keluhannya sendiri (milik orang lain -> 404).
            $locked = Complaint::visibleTo($request->user())->lockForUpdate()->findOrFail($complaint->id);

            if (! $locked->isResolved()) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya keluhan berstatus Resolved yang dapat dibuka kembali.',
                ]);
            }

            $locked->forceFill([
                'status' => ComplaintStatus::Pending,
                'resolved_by' => null,
                'resolved_at' => null,
            ])->save();
        }, 3);

        return $this->message('Keluhan dibuka kembali.');
    }

    private function tableQuery(Request $request): TableQuery
    {
        return TableQuery::for(Complaint::visibleTo($request->user())->with('user'))
            ->search(['title', 'body', 'user.name'])
            ->status('status', ComplaintStatus::class)
            ->sort([
                'title',
                'body',
                'created_at',
                'resolved_at',
                'user_name' => User::nameOf('complaints'),
            ]);
    }

    public function export(ExportRequest $request): StreamedResponse
    {
        $rows = $this->tableQuery($request)->export($request)->map(fn (Complaint $complaint) => [
            $complaint->user->name,
            $complaint->title,
            $complaint->body,
            $complaint->status->label(),
            $complaint->response,
            TableExport::wib($complaint->created_at),
            TableExport::wib($complaint->resolved_at),
        ]);

        return TableExport::download($request->validated('format'), 'Keluhan asrama',
            ['Pelapor', 'Judul', 'Isi', 'Status', 'Tanggapan', 'Diajukan', 'Resolved'], $rows->all());
    }
}
