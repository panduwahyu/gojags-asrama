<?php

namespace Tests\Unit;

use App\Enums\RoomStatus;
use PHPUnit\Framework\TestCase;

class RoomStatusTest extends TestCase
{
    public function test_from_counts_returns_expected_status(): void
    {
        $this->assertSame(RoomStatus::Empty, RoomStatus::fromCounts(0, 0, 2));
        $this->assertSame(RoomStatus::Assigned, RoomStatus::fromCounts(1, 0, 2));
        $this->assertSame(RoomStatus::Assigned, RoomStatus::fromCounts(2, 1, 2));
        $this->assertSame(RoomStatus::Occupied, RoomStatus::fromCounts(1, 1, 2));
        $this->assertSame(RoomStatus::Full, RoomStatus::fromCounts(2, 2, 2));

        $this->assertSame(RoomStatus::Assigned, RoomStatus::fromCounts(3, 2, 3));
        $this->assertSame(RoomStatus::Occupied, RoomStatus::fromCounts(2, 2, 3));
        $this->assertSame(RoomStatus::Full, RoomStatus::fromCounts(3, 3, 3));
    }
}
