<?php

namespace App\Enums;

enum LaundryStatus: string
{
    case Pending = 'pending';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Diajukan',
            self::Done => 'Selesai',
        };
    }
}
