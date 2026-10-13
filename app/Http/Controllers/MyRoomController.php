<?php

namespace App\Http\Controllers;

use App\Actions\CheckIn;
use App\Actions\CheckOut;
use App\Http\Resources\StayResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyRoomController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $slot = $user->slot()->with('room')->first();
        $stays = $user->stays()->orderByDesc('id')->get();

        return response()->json([
            'data' => [
                'room' => $slot ? [
                    'number' => $slot->room->number,
                    'floor' => $slot->room->floor,
                    'checked_in_at' => $slot->checked_in_at,
                ] : null,
                'stays' => StayResource::collection($stays)->resolve(),
            ],
        ]);
    }

    public function checkIn(Request $request, CheckIn $action): JsonResponse
    {
        $action->handle($request->user());

        return $this->message('Check-in berhasil.');
    }

    public function checkOut(Request $request, CheckOut $action): JsonResponse
    {
        $action->handle($request->user());

        return $this->message('Check-out berhasil. Slot kamar telah dilepaskan.');
    }
}
