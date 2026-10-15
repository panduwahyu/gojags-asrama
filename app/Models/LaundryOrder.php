<?php

namespace App\Models;

use App\Enums\LaundryStatus;
use App\Models\Concerns\VisibleToOwner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaundryOrder extends Model
{
    use HasFactory, VisibleToOwner;

    protected $fillable = [
        'items',
        'set_count',
    ];

    protected $casts = [
        'status' => LaundryStatus::class,
        'cost' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => LaundryStatus::Pending,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === LaundryStatus::Pending;
    }
}
