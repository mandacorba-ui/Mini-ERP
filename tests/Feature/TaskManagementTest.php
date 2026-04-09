<?php

namespace Tests\Feature;

use App\Domain\TaskManagement\Actions\AssignTaskAction;
use App\Domain\TaskManagement\Actions\CreateTaskAction;
use App\Domain\TaskManagement\Actions\LogTaskHoursAction;
use App\Domain\TaskManagement\Actions\TransitionTaskStatusAction;
use App\Domain\TaskManagement\Enums\TaskPriority;
use App\Domain\TaskManagement\Enums\TaskStatus;
use App\Domain\TaskManagement\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->employee = User::factory()->employee()->create();
    }

    // ── Create Task ──────────────────────────────────────────

    public function test_can_create_task(): void
    {
        $action = new CreateTaskAction();

        $task = $action->execute(
            title: 'Build login page',
            dueDate: Carbon::parse('2026-07-01'),
            estimatedHours: 8,
            priority: TaskPriority::High,
            description: 'Implement auth UI',
        );

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'todo']);
    }

    // ── Status Transitions ───────────────────────────────────

    public function test_can_transition_forward(): void
    {
        $task = $this->createTask();
        $action = new TransitionTaskStatusAction();

        $action->execute($task, TaskStatus::InProgress);
        $this->assertEquals(TaskStatus::InProgress, $task->fresh()->status);

        $action->execute($task, TaskStatus::InReview);
        $this->assertEquals(TaskStatus::InReview, $task->fresh()->status);

        $action->execute($task, TaskStatus::Done);
        $this->assertEquals(TaskStatus::Done, $task->fresh()->status);
    }

    public function test_cannot_transition_backward(): void
    {
        $task = $this->createTask(TaskStatus::InReview);
        $action = new TransitionTaskStatusAction();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only forward transitions');

        $action->execute($task, TaskStatus::Todo);
    }

    public function test_cannot_skip_to_same_status(): void
    {
        $task = $this->createTask(TaskStatus::InProgress);
        $action = new TransitionTaskStatusAction();

        $this->expectException(\DomainException::class);

        $action->execute($task, TaskStatus::InProgress);
    }

    public function test_can_skip_forward_statuses(): void
    {
        $task = $this->createTask();
        $action = new TransitionTaskStatusAction();

        // todo → done (skipping in_progress and in_review) is forward, so allowed
        $action->execute($task, TaskStatus::Done);
        $this->assertEquals(TaskStatus::Done, $task->fresh()->status);
    }

    // ── Assignment ───────────────────────────────────────────

    public function test_can_assign_user_to_task(): void
    {
        $task = $this->createTask();
        $action = new AssignTaskAction();

        $assignment = $action->execute($task, $this->employee);

        $this->assertDatabaseHas('task_assignments', [
            'task_id' => $task->id,
            'user_id' => $this->employee->id,
        ]);
    }

    public function test_multiple_users_can_be_assigned(): void
    {
        $task = $this->createTask();
        $action = new AssignTaskAction();
        $secondEmployee = User::factory()->employee()->create();

        $action->execute($task, $this->employee);
        $action->execute($task, $secondEmployee);

        $this->assertCount(2, $task->assignments);
    }

    public function test_cannot_assign_same_user_twice(): void
    {
        $task = $this->createTask();
        $action = new AssignTaskAction();

        $action->execute($task, $this->employee);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('already assigned');

        $action->execute($task, $this->employee);
    }

    // ── Log Hours ────────────────────────────────────────────

    public function test_can_log_hours_on_assignment(): void
    {
        $task = $this->createTask();
        $assignment = (new AssignTaskAction())->execute($task, $this->employee);

        $action = new LogTaskHoursAction();
        $action->execute($assignment, 3);

        $this->assertEquals(3, $assignment->fresh()->logged_hours);
    }

    public function test_logged_hours_accumulate(): void
    {
        $task = $this->createTask();
        $assignment = (new AssignTaskAction())->execute($task, $this->employee);

        $action = new LogTaskHoursAction();
        $action->execute($assignment, 2);
        $action->execute($assignment, 4);

        $this->assertEquals(6, $assignment->fresh()->logged_hours);
    }

    public function test_cannot_log_zero_or_negative_hours(): void
    {
        $task = $this->createTask();
        $assignment = (new AssignTaskAction())->execute($task, $this->employee);

        $this->expectException(\DomainException::class);

        (new LogTaskHoursAction())->execute($assignment, 0);
    }

    // ── Helpers ──────────────────────────────────────────────

    private function createTask(TaskStatus $status = TaskStatus::Todo): Task
    {
        return Task::create([
            'title' => 'Test task',
            'priority' => TaskPriority::Medium,
            'status' => $status,
            'due_date' => '2026-07-01',
            'estimated_hours' => 8,
        ]);
    }
}
