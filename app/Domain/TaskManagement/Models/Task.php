<?php

namespace App\Domain\TaskManagement\Models;

use App\Domain\TaskManagement\Enums\TaskPriority;
use App\Domain\TaskManagement\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'estimated_hours',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'estimated_hours' => 'integer',
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
        ];
    }

    // ── Relationships ────────────────────────────────────────

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('due_date', '<', now()->toDateString())
            ->where('status', '!=', TaskStatus::Done);
    }
}
