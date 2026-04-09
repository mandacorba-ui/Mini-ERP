<?php

namespace Database\Seeders;

use App\Domain\IdentityAndAccess\Enums\UserRole;
use App\Domain\TaskManagement\Enums\TaskPriority;
use App\Domain\TaskManagement\Enums\TaskStatus;
use App\Domain\TaskManagement\Models\Task;
use App\Domain\TaskManagement\Models\TaskAssignment;
use App\Domain\TimeTracking\Models\TimeLog;
use App\Domain\WorkforcePlanning\Enums\HolidayStatus;
use App\Domain\WorkforcePlanning\Enums\LeaveType;
use App\Domain\WorkforcePlanning\Enums\ShiftLabel;
use App\Domain\WorkforcePlanning\Models\Holiday;
use App\Domain\WorkforcePlanning\Models\Shift;
use App\Domain\WorkforcePlanning\Models\ShiftAssignment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ── 1. Users ─────────────────────────────────────────
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $manager = User::factory()->manager()->create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
        ]);

        $emp1 = User::factory()->employee()->create([
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
        ]);

        $emp2 = User::factory()->employee()->create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
        ]);

        $emp3 = User::factory()->employee()->create([
            'name' => 'Carol Davis',
            'email' => 'carol@example.com',
        ]);

        $employees = [$emp1, $emp2, $emp3];

        // ── 2. TimeLogs (past 7 days) ───────────────────────
        $this->seedTimeLogs([$emp1, $emp2, $emp3, $manager]);

        // ── 3. Holidays ─────────────────────────────────────
        $this->seedHolidays($employees, $manager);

        // ── 4. Tasks ─────────────────────────────────────────
        $this->seedTasks($employees, $manager);

        // ── 5. Shifts (next 7 days) ──────────────────────────
        $this->seedShifts($employees, $manager);
    }

    private function seedTimeLogs(array $users): void
    {
        $today = Carbon::today();

        foreach ($users as $user) {
            // Completed sessions for the past 6 weekdays
            for ($i = 6; $i >= 1; $i--) {
                $day = $today->copy()->subDays($i);

                // Skip weekends
                if ($day->isWeekend()) {
                    continue;
                }

                $clockIn = $day->copy()->setTime(rand(8, 9), rand(0, 30));
                $clockOut = $clockIn->copy()->addHours(rand(7, 9))->addMinutes(rand(0, 45));

                TimeLog::create([
                    'user_id' => $user->id,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                ]);
            }

            // Today: one active session (no clock_out) for the first two users
            if ($user === $users[0] || $user === $users[1]) {
                TimeLog::create([
                    'user_id' => $user->id,
                    'clock_in' => $today->copy()->setTime(9, 0),
                    'clock_out' => null,
                ]);
            } else {
                // Completed session today for others
                TimeLog::create([
                    'user_id' => $user->id,
                    'clock_in' => $today->copy()->setTime(8, 30),
                    'clock_out' => $today->copy()->setTime(12, 15),
                ]);
            }
        }
    }

    private function seedHolidays(array $employees, User $manager): void
    {
        $today = Carbon::today();
        $year = $today->year;

        // ── Alice (emp1): approved future holiday — blocks shifts on days +5..+7
        Holiday::create([
            'user_id' => $employees[0]->id,
            'approver_id' => $manager->id,
            'start_date' => $today->copy()->addDays(5),
            'end_date' => $today->copy()->addDays(7),
            'type' => LeaveType::Annual,
            'status' => HolidayStatus::Approved,
        ]);

        // ── Alice: pending request (visible in approvals UI)
        Holiday::create([
            'user_id' => $employees[0]->id,
            'start_date' => $today->copy()->addDays(30),
            'end_date' => $today->copy()->addDays(32),
            'type' => LeaveType::Annual,
            'status' => HolidayStatus::Pending,
        ]);

        // ── Bob (emp2): past approved holidays that consume most of his balance
        //    LeaveBalanceService::ANNUAL_ALLOWANCE = 20 days
        //    Verified weekday counts for 2026 calendar:
        //    Jan 5 (Mon) – Jan 16 (Fri) = 10 weekdays
        //    Feb 16 (Mon) – Feb 20 (Fri) = 5 weekdays
        //    Mar 16 (Mon) – Mar 17 (Tue) = 2 weekdays
        //    Total = 17 used → 3 remaining
        Holiday::create([
            'user_id' => $employees[1]->id,
            'approver_id' => $manager->id,
            'start_date' => Carbon::create($year, 1, 5),   // Mon Jan 5
            'end_date' => Carbon::create($year, 1, 16),    // Fri Jan 16  → 10 weekdays
            'type' => LeaveType::Annual,
            'status' => HolidayStatus::Approved,
        ]);
        Holiday::create([
            'user_id' => $employees[1]->id,
            'approver_id' => $manager->id,
            'start_date' => Carbon::create($year, 2, 16),  // Mon Feb 16
            'end_date' => Carbon::create($year, 2, 20),    // Fri Feb 20  → 5 weekdays
            'type' => LeaveType::Sick,
            'status' => HolidayStatus::Approved,
        ]);
        Holiday::create([
            'user_id' => $employees[1]->id,
            'approver_id' => $manager->id,
            'start_date' => Carbon::create($year, 3, 16),  // Mon Mar 16
            'end_date' => Carbon::create($year, 3, 17),    // Tue Mar 17  → 2 weekdays
            'type' => LeaveType::Personal,
            'status' => HolidayStatus::Approved,
        ]);

        // ── Bob: pending request for 5 days (will exceed balance if approved → edge case demo)
        Holiday::create([
            'user_id' => $employees[1]->id,
            'start_date' => $today->copy()->addDays(20),
            'end_date' => $today->copy()->addDays(26),
            'type' => LeaveType::Annual,
            'status' => HolidayStatus::Pending,
        ]);

        // ── Carol (emp3): pending request (visible in approvals UI)
        Holiday::create([
            'user_id' => $employees[2]->id,
            'start_date' => $today->copy()->addDays(10),
            'end_date' => $today->copy()->addDays(12),
            'type' => LeaveType::Personal,
            'status' => HolidayStatus::Pending,
        ]);
    }

    private function seedTasks(array $employees, User $manager): void
    {
        $today = Carbon::today();

        // ── Overdue: in_progress, assigned to Alice
        $overdueTask1 = Task::create([
            'title' => 'Complete Q1 report',
            'description' => 'Compile and submit the quarterly financial report.',
            'priority' => TaskPriority::High,
            'status' => TaskStatus::InProgress,
            'due_date' => $today->copy()->subDays(3),
            'estimated_hours' => 16,
        ]);
        TaskAssignment::create(['task_id' => $overdueTask1->id, 'user_id' => $employees[0]->id, 'logged_hours' => 10]);

        // ── Overdue: todo, assigned to Bob
        $overdueTask2 = Task::create([
            'title' => 'Update employee handbook',
            'description' => 'Review and update policies for the current year.',
            'priority' => TaskPriority::Medium,
            'status' => TaskStatus::Todo,
            'due_date' => $today->copy()->subDays(5),
            'estimated_hours' => 8,
        ]);
        TaskAssignment::create(['task_id' => $overdueTask2->id, 'user_id' => $employees[1]->id, 'logged_hours' => 0]);

        // ── Overdue: assigned to Manager (visible in manager dashboard)
        $overdueManager = Task::create([
            'title' => 'Review team performance reports',
            'description' => 'Compile and review Q1 performance data for all team members.',
            'priority' => TaskPriority::High,
            'status' => TaskStatus::InProgress,
            'due_date' => $today->copy()->subDays(2),
            'estimated_hours' => 8,
        ]);
        TaskAssignment::create(['task_id' => $overdueManager->id, 'user_id' => $manager->id, 'logged_hours' => 3]);

        // ── In-progress: due soon, multi-assigned
        $inProgressTask = Task::create([
            'title' => 'Onboarding flow redesign',
            'description' => 'Redesign the new hire onboarding experience.',
            'priority' => TaskPriority::High,
            'status' => TaskStatus::InProgress,
            'due_date' => $today->copy()->addDays(2),
            'estimated_hours' => 20,
        ]);
        TaskAssignment::create(['task_id' => $inProgressTask->id, 'user_id' => $employees[0]->id, 'logged_hours' => 8]);
        TaskAssignment::create(['task_id' => $inProgressTask->id, 'user_id' => $employees[2]->id, 'logged_hours' => 5]);

        // ── In-review: assigned to Bob
        $inReviewTask = Task::create([
            'title' => 'API documentation',
            'description' => 'Write comprehensive API docs for the internal service.',
            'priority' => TaskPriority::Medium,
            'status' => TaskStatus::InReview,
            'due_date' => $today->copy()->addDays(1),
            'estimated_hours' => 12,
        ]);
        TaskAssignment::create(['task_id' => $inReviewTask->id, 'user_id' => $employees[1]->id, 'logged_hours' => 11]);

        // ── Done: assigned to Carol
        $doneTask = Task::create([
            'title' => 'Set up CI/CD pipeline',
            'description' => 'Configure GitHub Actions for automated testing and deployment.',
            'priority' => TaskPriority::High,
            'status' => TaskStatus::Done,
            'due_date' => $today->copy()->subDays(1),
            'estimated_hours' => 10,
        ]);
        TaskAssignment::create(['task_id' => $doneTask->id, 'user_id' => $employees[2]->id, 'logged_hours' => 10]);

        // ── Todo: future, assigned to Alice
        $todoTask = Task::create([
            'title' => 'Research new scheduling library',
            'description' => 'Evaluate alternatives for the shift scheduling module.',
            'priority' => TaskPriority::Low,
            'status' => TaskStatus::Todo,
            'due_date' => $today->copy()->addDays(14),
            'estimated_hours' => 6,
        ]);
        TaskAssignment::create(['task_id' => $todoTask->id, 'user_id' => $employees[0]->id, 'logged_hours' => 0]);
    }

    private function seedShifts(array $employees, User $manager): void
    {
        $today = Carbon::today();

        // emp1 has approved holiday on days +5 to +7 — skip those for emp1
        $emp1HolidayStart = $today->copy()->addDays(5);
        $emp1HolidayEnd = $today->copy()->addDays(7);

        $shiftTemplates = [
            'morning' => ['08:00', '14:00'],
            'afternoon' => ['14:00', '20:00'],
            'night' => ['20:00', '02:00'],
        ];

        for ($i = 0; $i <= 7; $i++) {
            $day = $today->copy()->addDays($i);

            // Skip weekends for shifts
            if ($day->isWeekend()) {
                continue;
            }

            // Create morning + afternoon shifts each weekday
            foreach ([ShiftLabel::Morning, ShiftLabel::Afternoon] as $label) {
                [$start, $end] = $shiftTemplates[$label->value];

                $shift = Shift::create([
                    'date' => $day,
                    'start_time' => $start,
                    'end_time' => $end,
                    'label' => $label,
                ]);

                // Assign employees — respect holiday constraints
                foreach ($employees as $emp) {
                    // Skip emp1 during their approved holiday
                    if ($emp === $employees[0]
                        && $day->gte($emp1HolidayStart)
                        && $day->lte($emp1HolidayEnd)) {
                        continue;
                    }

                    // Assign each employee to one shift type per day to avoid overlap
                    // Morning: emp1, emp3; Afternoon: emp2, manager
                    if ($label === ShiftLabel::Morning && in_array($emp, [$employees[0], $employees[2]])) {
                        ShiftAssignment::create(['shift_id' => $shift->id, 'user_id' => $emp->id]);
                    } elseif ($label === ShiftLabel::Afternoon && $emp === $employees[1]) {
                        ShiftAssignment::create(['shift_id' => $shift->id, 'user_id' => $emp->id]);
                    }
                }

                // Manager gets morning shifts
                if ($label === ShiftLabel::Morning) {
                    ShiftAssignment::create(['shift_id' => $shift->id, 'user_id' => $manager->id]);
                }
            }
        }
    }
}
