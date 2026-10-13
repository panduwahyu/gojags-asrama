<?php

namespace App\Http\Controllers;

use App\Enums\RoomStatus;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Support\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoomController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $rooms = TableQuery::for(Room::withOccupancy()->with('slots.user'))
            ->search(['number', 'floor'])
            ->sort([
                'number',
                'floor',
                'assigned_count',
                'status' => fn (Builder $query, string $direction) => $query->orderByStatus($direction),
                'resident' => fn (Builder $query, string $direction) => $query->orderByFirstResident($direction),
            ])
            ->paginate($request);

        return RoomResource::collection($rooms)->additional([
            'monitoring' => $this->monitoring(),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function monitoring(): array
    {
        $counts = Room::withOccupancy()
            ->get()
            ->countBy(fn (Room $room) => $room->status->value);

        $summary = [];
        foreach (RoomStatus::cases() as $status) {
            $summary[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $summary;
    }
}
