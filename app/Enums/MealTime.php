<?php

namespace App\Enums;

enum MealTime: string
{
    case Breakfast = 'breakfast';
    case Lunch = 'lunch';
    case Dinner = 'dinner';

    public function label(): string
    {
        return match ($this) {
            self::Breakfast => 'Pagi',
            self::Lunch => 'Siang',
            self::Dinner => 'Malam',
        };
    }
}
