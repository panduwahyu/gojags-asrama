<?php

namespace App\Enums;

enum RoomStatus: string
{
    case Empty = 'empty';
    case Assigned = 'assigned';
    case Occupied = 'occupied';
    case Full = 'full';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Empty => 'Kosong',
            self::Assigned => 'Assigned',
            self::Occupied => 'Terisi',
            self::Full => 'Penuh',
            self::Inactive => 'Nonaktif',
        };
    }

    public static function fromCounts(int $assigned, int $checkedIn, int $capacity): self
    {
        if ($assigned === 0) {
            return self::Empty;
        }

        if ($assigned > $checkedIn) {
            return self::Assigned;
        }

        if ($checkedIn >= $capacity) {
            return self::Full;
        }

        return self::Occupied;
    }
}
