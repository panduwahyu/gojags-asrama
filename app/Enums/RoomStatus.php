<?php

namespace App\Enums;

enum RoomStatus: string
{
    case Empty = 'empty';
    case Assigned = 'assigned';
    case Occupied = 'occupied';
    case Full = 'full';

    public function label(): string
    {
        return match ($this) {
            self::Empty => 'Kosong',
            self::Assigned => 'Assigned',
            self::Occupied => 'Terisi',
            self::Full => 'Penuh',
        };
    }

    public static function fromCounts(int $assigned, int $checkedIn): self
    {
        if ($assigned === 0) {
            return self::Empty;
        }

        if ($assigned > $checkedIn) {
            return self::Assigned;
        }

        if ($checkedIn >= 2) {
            return self::Full;
        }

        return self::Occupied;
    }
}
