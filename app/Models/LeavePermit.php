<?php

namespace App\Models;

use App\Enums\PermitStatus;
use App\Models\Concerns\VisibleToOwner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeavePermit extends Model
{
    use HasFactory, VisibleToOwner;

    protected $fillable = [
        'leaves_at',
        'returns_at',
        'reason',
    ];

    // rejection_reason diisi lewat peninjauan admin, bukan oleh pengaju.

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

    public function isPending(): bool
    {
        return $this->status === PermitStatus::Pending;
    }
}
