<?php

namespace App\Domain\TaskManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAssignment extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'task_id',
        'user_id',
        'logged_hours',
    ];

    protected function casts(): array
    {
        return [
            'logged_hours' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
