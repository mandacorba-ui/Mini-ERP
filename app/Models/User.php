<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\IdentityAndAccess\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUlids, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    // ── Relationship placeholders ────────────────────────────

    public function timeLogs(): HasMany
    {
        return $this->hasMany(\App\Domain\TimeTracking\Models\TimeLog::class);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(\App\Domain\WorkforcePlanning\Models\Holiday::class);
    }

    public function taskAssignments(): HasMany
    {
        return $this->hasMany(\App\Domain\TaskManagement\Models\TaskAssignment::class);
    }

    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(\App\Domain\WorkforcePlanning\Models\ShiftAssignment::class);
    }
}
