<?php

namespace App\Models;

use App\Enums\CateringStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CateringMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_number',
        'provider',
        'served_at',
    ];

    protected $casts = [
        'served_at' => 'datetime',
        'status' => CateringStatus::class,
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
