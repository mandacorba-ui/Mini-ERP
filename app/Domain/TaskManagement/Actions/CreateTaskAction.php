<?php

namespace App\Domain\TaskManagement\Actions;

use App\Domain\TaskManagement\Enums\TaskPriority;
use App\Domain\TaskManagement\Enums\TaskStatus;
use App\Domain\TaskManagement\Models\Task;
use Illuminate\Support\Carbon;

class CreateTaskAction
{
    public function execute(
        string $title,
        Carbon $dueDate,
        int $estimatedHours,
        TaskPriority $priority = TaskPriority::Medium,
        ?string $description = null,
    ): Task {
        return Task::create([
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'status' => TaskStatus::Todo,
            'due_date' => $dueDate,
            'estimated_hours' => $estimatedHours,
        ]);
    }
}
