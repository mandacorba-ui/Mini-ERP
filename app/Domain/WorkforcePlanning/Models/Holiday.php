<?php

namespace App\Domain\WorkforcePlanning\Models;

use App\Domain\WorkforcePlanning\Enums\HolidayStatus;
use App\Domain\WorkforcePlanning\Enums\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'approver_id',
        'start_date',
        'end_date',
        'type',
        'status',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'type' => LeaveType::class,
            'status' => HolidayStatus::class,
        ];
    }

    // ── Relationships ────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', HolidayStatus::Pending);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', HolidayStatus::Approved);
    }

    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
