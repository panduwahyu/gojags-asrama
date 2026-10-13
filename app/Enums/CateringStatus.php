<?php

namespace App\Enums;

enum CateringStatus: string
{
    case Scheduled = 'scheduled';
    case Preparing = 'preparing';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Terjadwal',
            self::Preparing => 'Disiapkan',
            self::Done => 'Selesai',
        };
    }
}
