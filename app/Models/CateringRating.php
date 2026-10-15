<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CateringRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'catering_menu_id',
        'user_id',
        'rating',
    ];

    public function cateringMenu(): BelongsTo
    {
        return $this->belongsTo(CateringMenu::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
