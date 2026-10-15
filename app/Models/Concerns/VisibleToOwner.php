<?php

namespace App\Models\Concerns;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Penghuni hanya melihat baris miliknya; admin melihat semuanya.
 * Model pemakai wajib punya relasi user().
 */
trait VisibleToOwner
{
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->role === Role::Resident) {
            $query->whereBelongsTo($user);
        }
    }
}
