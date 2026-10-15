<?php

namespace App\Http\Controllers;

use App\Enums\RoomStatus;
use App\Http\Requests\ExportRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Models\RoomSlot;
use App\Support\RoomLocks;
use App\Support\TableExport;
use App\Support\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RoomController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $rooms = $this->tableQuery()->paginate($request);

        return RoomResource::collection($rooms)->additional([
            'monitoring' => $this->monitoring(),
        ]);
    }

    /** Menandai kamar aktif/nonaktif (rusak, perbaikan). Kamar berpenghuni tidak boleh dinonaktifkan. */
    public function update(UpdateRoomRequest $request, Room $room): JsonResponse
    {
        $active = $request->boolean('is_active');

        DB::transaction(function () use ($room, $active) {
            RoomLocks::acquireAll();

            $hasResidents = RoomSlot::where('room_id', $room->id)->whereNotNull('user_id')->exists();
            if (! $active && $hasResidents) {
                throw ValidationException::withMessages([
                    'is_active' => 'Kamar masih berpenghuni. Cabut alokasi atau pindahkan penghuni terlebih dahulu.',
                ]);
            }

            $room->update(['is_active' => $active]);
        }, 3);

        return $this->message($active ? 'Kamar diaktifkan.' : 'Kamar dinonaktifkan.');
    }

    public function export(ExportRequest $request): StreamedResponse
    {
        $rows = $this->tableQuery()->export($request)->map(fn (Room $room) => [
            $room->number,
            $room->floor,
            $room->status->label(),
            $room->slots->filter(fn ($slot) => $slot->user !== null)->map(fn ($slot) => $slot->user->name)->sort()->implode(', '),
        ]);

        return TableExport::download($request->validated('format'), 'Kamar',
            ['Kamar', 'Lantai', 'Status', 'Penghuni'], $rows->all());
    }

    private function tableQuery(): TableQuery
    {
        return TableQuery::for(Room::withOccupancy()->with('slots.user'))
            ->search(['number', 'floor'])
            ->sort([
                'number',
                'floor',
                'assigned_count',
                'status' => fn (Builder $query, string $direction) => $query->orderByStatus($direction),
                'resident' => fn (Builder $query, string $direction) => $query->orderByFirstResident($direction),
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
