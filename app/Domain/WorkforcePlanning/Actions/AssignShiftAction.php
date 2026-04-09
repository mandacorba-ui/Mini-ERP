<?php

namespace App\Domain\WorkforcePlanning\Actions;

use App\Domain\WorkforcePlanning\Models\Holiday;
use App\Domain\WorkforcePlanning\Models\Shift;
use App\Domain\WorkforcePlanning\Models\ShiftAssignment;
use App\Domain\WorkforcePlanning\Services\OverlapDetectionService;
use App\Models\User;
use Illuminate\Support\Carbon;

class AssignShiftAction
{
    public function __construct(
        private OverlapDetectionService $overlapService,
    ) {}

    public function execute(Shift $shift, User $user): ShiftAssignment
    {
        $this->ensureNoOverlappingShifts($shift, $user);
        $this->ensureNotOnApprovedLeave($shift, $user);

        return ShiftAssignment::create([
            'shift_id' => $shift->id,
            'user_id' => $user->id,
        ]);
    }

    private function ensureNoOverlappingShifts(Shift $shift, User $user): void
    {
        $shiftStart = Carbon::parse($shift->date->format('Y-m-d') . ' ' . $shift->start_time);
        $shiftEnd = Carbon::parse($shift->date->format('Y-m-d') . ' ' . $shift->end_time);

        $existingAssignments = ShiftAssignment::where('user_id', $user->id)
            ->with('shift')
            ->get();

        foreach ($existingAssignments as $assignment) {
            $existingShift = $assignment->shift;

            if (! $existingShift->date->equalTo($shift->date)) {
                continue;
            }

            $existingStart = Carbon::parse($existingShift->date->format('Y-m-d') . ' ' . $existingShift->start_time);
            $existingEnd = Carbon::parse($existingShift->date->format('Y-m-d') . ' ' . $existingShift->end_time);

            if ($this->overlapService->hasOverlap($shiftStart, $shiftEnd, $existingStart, $existingEnd)) {
                throw new \DomainException('Employee already has an overlapping shift on this date.');
            }
        }
    }

    private function ensureNotOnApprovedLeave(Shift $shift, User $user): void
    {
        $onLeave = Holiday::forUser($user->id)
            ->approved()
            ->where('start_date', '<=', $shift->date)
            ->where('end_date', '>=', $shift->date)
            ->exists();

        if ($onLeave) {
            throw new \DomainException('Employee has approved leave on this date and cannot be assigned a shift.');
        }
    }
}
