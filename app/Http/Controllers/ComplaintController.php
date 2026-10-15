<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintStatus;
use App\Http\Requests\StoreComplaintRequest;
use App\Http\Resources\ComplaintResource;
use App\Models\Complaint;
use App\Models\User;
use App\Support\TableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ComplaintController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $complaints = TableQuery::for(Complaint::visibleTo($request->user())->with('user'))
            ->search(['title', 'body', 'user.name'])
            ->status('status', ComplaintStatus::class)
            ->sort([
                'title',
                'body',
                'created_at',
                'resolved_at',
                'user_name' => User::nameOf('complaints'),
            ])
            ->paginate($request);

        return ComplaintResource::collection($complaints);
    }

    public function store(StoreComplaintRequest $request): JsonResponse
    {
        $request->user()->complaints()->create($request->validated());

        return $this->message('Keluhan asrama berhasil dikirim.', 201);
    }

    public function resolve(Request $request, Complaint $complaint): JsonResponse
    {
        DB::transaction(function () use ($request, $complaint) {
            $locked = Complaint::lockForUpdate()->findOrFail($complaint->id);

            if ($locked->isPending()) {
                $locked->forceFill([
                    'status' => ComplaintStatus::Resolved,
                    'resolved_by' => $request->user()->id,
                    'resolved_at' => now(),
                ])->save();
            }
        }, 3);

        return $this->message('Keluhan ditandai Resolved.');
    }
}
