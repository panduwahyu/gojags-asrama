<?php

namespace App\Http\Controllers;

use App\Actions\AssignRoomSlot;
use App\Actions\MoveResidentRoom;
use App\Http\Requests\PlaceResidentRequest;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/** Penempatan dan pemindahan manual oleh petugas. Peringatan butuh konfirmasi (409, lalu confirm=true). */
class RoomPlacementController extends Controller
{
    public function assign(PlaceResidentRequest $request, Room $room, AssignRoomSlot $action): JsonResponse
    {
        $resident = User::findOrFail($request->validated('user_id'));
        $warnings = $action->handle($resident, $room, $request->boolean('confirm'));

        return response()->json([
            'message' => "{$resident->name} ditempatkan di kamar {$room->number}.",
            'warnings' => $warnings,
        ]);
    }

    public function move(PlaceResidentRequest $request, Room $room, MoveResidentRoom $action): JsonResponse
    {
        $resident = User::findOrFail($request->validated('user_id'));
        $warnings = $action->handle($resident, $room, $request->boolean('confirm'));

        return response()->json([
            'message' => "{$resident->name} dipindahkan ke kamar {$room->number}.",
            'warnings' => $warnings,
        ]);
    }
}
