<?php

namespace App\Models;

use App\Enums\PermitStatus;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeavePermit extends Model
{
    use HasFactory;

    protected $fillable = [
        'leaves_at',
        'returns_at',
        'reason',
    ];

    protected $casts = [
        'leaves_at' => 'datetime',
        'returns_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'status' => PermitStatus::class,
    ];

    protected $attributes = [
        'status' => PermitStatus::Pending,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Penghuni hanya melihat miliknya; admin melihat semuanya. */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->role === Role::Resident) {
            $query->whereBelongsTo($user);
        }
    }

    public function isPending(): bool
    {
        return $this->status === PermitStatus::Pending;
    }
}
