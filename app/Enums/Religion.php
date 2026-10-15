<?php

namespace App\Enums;

enum Religion: string
{
    case Islam = 'islam';
    case Kristen = 'kristen';
    case Katolik = 'katolik';
    case Hindu = 'hindu';
    case Buddha = 'buddha';
    case Konghucu = 'konghucu';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
