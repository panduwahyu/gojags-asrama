<?php

namespace App\Http\Controllers;

use App\Actions\AllocateRooms;
use Illuminate\Http\JsonResponse;

class RoomAllocationController extends Controller
{
    public function __invoke(AllocateRooms $action): JsonResponse
    {
        $result = $action->handle();

        $allocatedCount = count($result['allocated']);
        $incompleteCount = count($result['incomplete']);
        $waitingCount = count($result['waiting']);

        $message = "{$allocatedCount} user dialokasikan. "
            ."{$incompleteCount} profil belum lengkap. "
            ."{$waitingCount} user menunggu kamar yang sesuai.";

        return response()->json(['message' => $message] + $result);
    }
}
