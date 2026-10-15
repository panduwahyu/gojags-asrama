<?php

namespace App\Models;

use App\Enums\RoomStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'floor',
    ];

    public function slots(): HasMany
    {
        return $this->hasMany(RoomSlot::class);
    }

    public function stays(): HasMany
    {
        return $this->hasMany(Stay::class);
    }

    public function scopeWithOccupancy(Builder $query): void
    {
        $query->withCount([
            'slots as assigned_count' => fn (Builder $slots) => $slots->whereNotNull('user_id'),
            'slots as checked_in_count' => fn (Builder $slots) => $slots
                ->whereNotNull('user_id')
                ->whereNotNull('checked_in_at'),
        ]);
    }

    /**
     * Urutan mengikuti RoomStatus::fromCounts: Kosong (0) < Assigned (1) < Terisi (2) < Penuh (3).
     */
    public function scopeOrderByStatus(Builder $query, string $direction): void
    {
        $dir = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';
        $capacity = (int) config('asrama.room_capacity');

        $query->orderByRaw(
            "CASE
                WHEN assigned_count = 0 THEN 0
                WHEN assigned_count > checked_in_count THEN 1
                WHEN checked_in_count >= ? THEN 3
                ELSE 2
            END {$dir}",
            [$capacity]
        );
    }

    public function scopeOrderByFirstResident(Builder $query, string $direction): void
    {
        $firstResident = User::select('users.name')
            ->join('room_slots', 'room_slots.user_id', '=', 'users.id')
            ->whereColumn('room_slots.room_id', 'rooms.id')
            ->orderBy('users.name')
            ->limit(1);

        $query->orderBy($firstResident, $direction);
    }

    protected function status(): Attribute
    {
        return Attribute::get(
            fn () => RoomStatus::fromCounts(
                (int) $this->assigned_count,
                (int) $this->checked_in_count,
                (int) config('asrama.room_capacity')
            )
        );
    }
}
