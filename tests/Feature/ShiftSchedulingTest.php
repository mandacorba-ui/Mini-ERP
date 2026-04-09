<?php

namespace Tests\Feature;

use App\Domain\WorkforcePlanning\Actions\AssignShiftAction;
use App\Domain\WorkforcePlanning\Enums\HolidayStatus;
use App\Domain\WorkforcePlanning\Enums\LeaveType;
use App\Domain\WorkforcePlanning\Enums\ShiftLabel;
use App\Domain\WorkforcePlanning\Models\Holiday;
use App\Domain\WorkforcePlanning\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->employee = User::factory()->employee()->create();
    }

    // ── Assignment ───────────────────────────────────────────

    public function test_can_assign_employee_to_shift(): void
    {
        $shift = $this->createShift('2026-07-01', '08:00', '16:00', ShiftLabel::Morning);

        $action = app(AssignShiftAction::class);
        $assignment = $action->execute($shift, $this->employee);

        $this->assertDatabaseHas('shift_assignments', [
            'shift_id' => $shift->id,
            'user_id' => $this->employee->id,
        ]);
    }

    public function test_multiple_employees_can_be_assigned_to_same_shift(): void
    {
        $shift = $this->createShift('2026-07-01', '08:00', '16:00', ShiftLabel::Morning);
        $secondEmployee = User::factory()->employee()->create();

        $action = app(AssignShiftAction::class);
        $action->execute($shift, $this->employee);
        $action->execute($shift, $secondEmployee);

        $this->assertCount(2, $shift->assignments);
    }

    // ── Overlap Prevention ───────────────────────────────────

    public function test_cannot_assign_overlapping_shifts(): void
    {
        $morning = $this->createShift('2026-07-01', '08:00', '16:00', ShiftLabel::Morning);
        $overlapping = $this->createShift('2026-07-01', '12:00', '20:00', ShiftLabel::Afternoon);

        $action = app(AssignShiftAction::class);
        $action->execute($morning, $this->employee);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('overlapping shift');

        $action->execute($overlapping, $this->employee);
    }

    public function test_can_assign_non_overlapping_shifts_same_day(): void
    {
        $morning = $this->createShift('2026-07-01', '06:00', '14:00', ShiftLabel::Morning);
        $night = $this->createShift('2026-07-01', '22:00', '23:59', ShiftLabel::Night);

        $action = app(AssignShiftAction::class);
        $action->execute($morning, $this->employee);
        $action->execute($night, $this->employee);

        $this->assertCount(2, $this->employee->shiftAssignments);
    }

    public function test_can_assign_shifts_on_different_days(): void
    {
        $day1 = $this->createShift('2026-07-01', '08:00', '16:00', ShiftLabel::Morning);
        $day2 = $this->createShift('2026-07-02', '08:00', '16:00', ShiftLabel::Morning);

        $action = app(AssignShiftAction::class);
        $action->execute($day1, $this->employee);
        $action->execute($day2, $this->employee);

        $this->assertCount(2, $this->employee->shiftAssignments);
    }

    // ── Leave Conflict ───────────────────────────────────────

    public function test_cannot_assign_shift_during_approved_leave(): void
    {
        Holiday::create([
            'user_id' => $this->employee->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-05',
            'type' => LeaveType::Annual,
            'status' => HolidayStatus::Approved,
        ]);

        $shift = $this->createShift('2026-07-03', '08:00', '16:00', ShiftLabel::Morning);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('approved leave');

        app(AssignShiftAction::class)->execute($shift, $this->employee);
    }

    public function test_pending_leave_does_not_block_shift(): void
    {
        Holiday::create([
            'user_id' => $this->employee->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-05',
            'type' => LeaveType::Annual,
            'status' => HolidayStatus::Pending,
        ]);

        $shift = $this->createShift('2026-07-03', '08:00', '16:00', ShiftLabel::Morning);

        $assignment = app(AssignShiftAction::class)->execute($shift, $this->employee);

        $this->assertNotNull($assignment->id);
    }

    // ── Helpers ──────────────────────────────────────────────

    private function createShift(string $date, string $startTime, string $endTime, ShiftLabel $label): Shift
    {
        return Shift::create([
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'label' => $label,
        ]);
    }
}
