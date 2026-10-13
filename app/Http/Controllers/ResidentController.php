<?php

namespace App\Http\Controllers;

use App\Actions\UpdateResidentProfile;
use App\Http\Requests\UpdateResidentRequest;
use App\Http\Resources\ResidentResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ResidentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $residents = User::residents()
            ->with('slot')
            ->orderBy('id')
            ->get();

        return ResidentResource::collection($residents);
    }

    public function update(
        UpdateResidentRequest $request,
        User $resident,
        UpdateResidentProfile $action
    ): JsonResponse {
        $action->handle($resident, $request->validated());

        return $this->message('Profil alokasi berhasil disimpan.');
    }
}
