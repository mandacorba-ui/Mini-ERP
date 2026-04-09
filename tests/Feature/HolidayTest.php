<?php

namespace Tests\Feature;

use App\Domain\WorkforcePlanning\Actions\ApproveHolidayAction;
use App\Domain\WorkforcePlanning\Actions\RejectHolidayAction;
use App\Domain\WorkforcePlanning\Actions\RequestHolidayAction;
use App\Domain\WorkforcePlanning\Enums\HolidayStatus;
use App\Domain\WorkforcePlanning\Enums\LeaveType;
use App\Domain\WorkforcePlanning\Models\Holiday;
use App\Domain\WorkforcePlanning\Services\LeaveBalanceService;
use App\Domain\WorkforcePlanning\Services\OverlapDetectionService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HolidayTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->employee = User::factory()->employee()->create();
        $this->manager = User::factory()->manager()->create();
    }

    // ── Request Holiday ──────────────────────────────────────

    public function test_employee_can_request_holiday(): void
    {
        $action = app(RequestHolidayAction::class);

        $holiday = $action->execute(
            $this->employee,
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-05'),
            LeaveType::Annual,
        );

        $this->assertDatabaseHas('holidays', ['id' => $holiday->id]);
        $this->assertEquals(HolidayStatus::Pending, $holiday->status);
    }

    public function test_cannot_request_overlapping_holiday(): void
    {
        // Create and approve an existing holiday first
        Holiday::create([
            'user_id' => $this->employee->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-05',
            'type' => LeaveType::Annual,
            'status' => HolidayStatus::Approved,
        ]);

        $action = app(RequestHolidayAction::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('overlaps');

        $action->execute(
            $this->employee,
            Carbon::parse('2026-06-03'),
            Carbon::parse('2026-06-10'),
            LeaveType::Annual,
        );
    }

    // ── Approve Holiday ──────────────────────────────────────

    public function test_manager_can_approve_holiday(): void
    {
        $holiday = Holiday::create([
            'user_id' => $this->employee->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-05',
            'type' => LeaveType::Annual,
            'status' => HolidayStatus::Pending,
        ]);

        $action = app(ApproveHolidayAction::class);
        $result = $action->execute($holiday, $this->manager);

        $this->assertEquals(HolidayStatus::Approved, $result->status);
        $this->assertEquals($this->manager->id, $result->approver_id);
    }

    public function test_manager_cannot_approve_own_request(): void
    {
        $holiday = Holiday::create([
            'user_id' => $this->manager->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-05',
            'type' => LeaveType::Annual,
            'status' => HolidayStatus::Pending,
        ]);

        $action = app(ApproveHolidayAction::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('cannot approve your own');

        $action->execute($holiday, $this->manager);
    }

    public function test_cannot_approve_if_insufficient_balance(): void
    {
        // Use up all 20 days of annual leave
        Holiday::create([
            'user_id' => $this->employee->id,
            'start_date' => '2026-01-05', // Monday
            'end_date' => '2026-01-30',   // Friday — 20 weekdays
            'type' => LeaveType::Annual,
            'status' => HolidayStatus::Approved,
        ]);

        $holiday = Holiday::create([
            'user_id' => $this->employee->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
            'type' => LeaveType::Annual,
            'status' => HolidayStatus::Pending,
        ]);

        $action = app(ApproveHolidayAction::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Insufficient leave balance');

        $action->execute($holiday, $this->manager);
    }

    public function test_employee_cannot_approve_holiday(): void
    {
        $otherEmployee = User::factory()->employee()->create();

        $holiday = Holiday::create([
            'user_id' => $this->employee->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-05',
            'type' => LeaveType::Annual,
            'status' => HolidayStatus::Pending,
        ]);

        $action = app(ApproveHolidayAction::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only managers or admins');

        $action->execute($holiday, $otherEmployee);
    }

    public function test_cannot_approve_holiday_exceeding_annual_allowance(): void
    {
        // 30 weekdays — exceeds the 20-day annual allowance
        $holiday = Holiday::create([
            'user_id' => $this->employee->id,
            'start_date' => '2026-03-02', // Monday
            'end_date' => '2026-04-10',   // Friday — 30 weekdays
            'type' => LeaveType::Annual,
            'status' => HolidayStatus::Pending,
        ]);

        $action = app(ApproveHolidayAction::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Insufficient leave balance');

        $action->execute($holiday, $this->manager);
    }

    // ── Reject Holiday ───────────────────────────────────────

    public function test_holiday_can_be_rejected(): void
    {
        $holiday = Holiday::create([
            'user_id' => $this->employee->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-05',
            'type' => LeaveType::Annual,
            'status' => HolidayStatus::Pending,
        ]);

        $action = new RejectHolidayAction();
        $result = $action->execute($holiday, 'Not enough coverage.');

        $this->assertEquals(HolidayStatus::Rejected, $result->status);
        $this->assertEquals('Not enough coverage.', $result->comment);
    }

    // ── Leave Balance ────────────────────────────────────────

    public function test_weekday_calculation_is_correct(): void
    {
        $service = new LeaveBalanceService();

        // Mon Jun 1 to Fri Jun 5 = 5 weekdays
        $days = $service->calculateRequestedDays(
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-05'),
        );

        $this->assertEquals(5, $days);
    }

    public function test_weekday_calculation_excludes_weekends(): void
    {
        $service = new LeaveBalanceService();

        // Mon Jun 1 to Sun Jun 7 = 5 weekdays (skips Sat+Sun)
        $days = $service->calculateRequestedDays(
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-07'),
        );

        $this->assertEquals(5, $days);
    }
}
