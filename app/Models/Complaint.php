<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'status' => ComplaintStatus::class,
    ];

    protected $attributes = [
        'status' => ComplaintStatus::Pending,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->role === Role::Resident) {
            $query->whereBelongsTo($user);
        }
    }

    public function isPending(): bool
    {
        return $this->status === ComplaintStatus::Pending;
    }
}
