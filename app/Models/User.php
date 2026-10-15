<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\Religion;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'cohort',
        'gender',
        'religion',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role' => Role::class,
        'gender' => Gender::class,
        'religion' => Religion::class,
    ];

    protected $attributes = [
        'role' => Role::Resident,
    ];

    public function slot(): HasOne
    {
        return $this->hasOne(RoomSlot::class);
    }

    public function stays(): HasMany
    {
        return $this->hasMany(Stay::class);
    }

    public function latestStay(): HasOne
    {
        return $this->hasOne(Stay::class)->latestOfMany();
    }

    public function laundryOrders(): HasMany
    {
        return $this->hasMany(LaundryOrder::class);
    }

    public function leavePermits(): HasMany
    {
        return $this->hasMany(LeavePermit::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function cateringRatings(): HasMany
    {
        return $this->hasMany(CateringRating::class);
    }

    public function scopeResidents(Builder $query): void
    {
        $query->where('role', Role::Resident);
    }

    /** Penghuni aktif menginap: punya kamar dan sudah check-in (belum check-out). */
    public function isStaying(): bool
    {
        return $this->slot()->whereNotNull('checked_in_at')->exists();
    }

    /** Angkatan dibandingkan tanpa beda huruf besar/kecil dan spasi berlebih. */
    public static function normalizeProfileText(?string $value): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $value ?? '')));
    }

    public static function nameOf(string $table): Builder
    {
        return static::select('name')->whereColumn('users.id', $table.'.user_id');
    }
}
