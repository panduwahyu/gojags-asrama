<?php

namespace App\Models;

use App\Enums\CateringStatus;
use App\Enums\MealTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class CateringMenu extends Model
{
    use HasFactory;

    public function ratingClosesAt(): Carbon
    {
        $timezone = (string) config('asrama.display_timezone', 'Asia/Jakarta');

        return $this->served_at->copy()->setTimezone($timezone)->endOfDay()->utc();
    }

    public function canBeRated(): bool
    {
        $now = now();

        return $now->gte($this->served_at) && $now->lte($this->ratingClosesAt());
    }

    protected $fillable = [
        'menu_number',
        'dish_name',
        'meal_time',
        'provider',
        'served_at',
    ];

    protected $casts = [
        'served_at' => 'datetime',
        'status' => CateringStatus::class,
        'meal_time' => MealTime::class,
    ];

    protected $attributes = [
        'status' => CateringStatus::Scheduled,
    ];

    public function ratings(): HasMany
    {
        return $this->hasMany(CateringRating::class);
    }

    public function scopeWithRatingsFor(Builder $query, User $user): void
    {
        $query
            ->withAvg('ratings as rating_average', 'rating')
            ->withCount('ratings as rating_count')
            ->addSelect([
                'my_rating' => CateringRating::select('rating')
                    ->whereColumn('catering_menu_id', 'catering_menus.id')
                    ->where('user_id', $user->id)
                    ->limit(1),
            ]);
    }
}
