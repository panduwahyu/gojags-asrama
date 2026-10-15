<?php

namespace App\Enums;

enum ComplaintStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Diajukan',
            self::InProgress => 'Sedang ditangani',
            self::Resolved => 'Resolved',
        };
    }
}
