<?php

namespace App\Domain\WorkforcePlanning\Models;

use App\Domain\WorkforcePlanning\Enums\ShiftLabel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'date',
        'start_time',
        'end_time',
        'label',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'label' => ShiftLabel::class,
        ];
    }

    // ── Relationships ────────────────────────────────────────

    public function assignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }
}
