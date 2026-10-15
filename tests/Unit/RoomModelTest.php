<?php

namespace Tests\Unit;

use App\Enums\RoomStatus;
use App\Models\Room;
use Tests\TestCase;

class RoomModelTest extends TestCase
{
    public function test_room_status_accessor_reflects_counts(): void
    {
        $makeRoom = function (int $assigned, int $checkedIn): Room {
            $room = new Room;
            $room->assigned_count = $assigned;
            $room->checked_in_count = $checkedIn;

            return $room;
        };

        // 0 assigned => Empty
        $this->assertSame(RoomStatus::Empty, $makeRoom(0, 0)->status);

        // 1 assigned, 0 checked in => Assigned
        $this->assertSame(RoomStatus::Assigned, $makeRoom(1, 0)->status);

        // 1 assigned, 1 checked in => Occupied
        $this->assertSame(RoomStatus::Occupied, $makeRoom(1, 1)->status);

        // 2 assigned, 1 checked in => Assigned (belum semua check in)
        $this->assertSame(RoomStatus::Assigned, $makeRoom(2, 1)->status);

        // 2 assigned, 2 checked in => Full (kapasitas default 2)
        $this->assertSame(RoomStatus::Full, $makeRoom(2, 2)->status);
    }

    public function test_room_status_accessor_with_custom_capacity(): void
    {
        config(['asrama.room_capacity' => 3]);

        $makeRoom = function (int $assigned, int $checkedIn): Room {
            $room = new Room;
            $room->assigned_count = $assigned;
            $room->checked_in_count = $checkedIn;

            return $room;
        };

        // 2 assigned, 2 checked in dengan kapasitas 3 => Occupied (bukan Full)
        $this->assertSame(RoomStatus::Occupied, $makeRoom(2, 2)->status);

        // 3 assigned, 3 checked in dengan kapasitas 3 => Full
        $this->assertSame(RoomStatus::Full, $makeRoom(3, 3)->status);
    }
}
