<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Resident = 'resident';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Resident => 'Penghuni',
        };
    }
}
