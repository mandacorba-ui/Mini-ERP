<?php

namespace App\Domain\TaskManagement\Actions;

use App\Domain\TaskManagement\Models\Task;
use App\Domain\TaskManagement\Models\TaskAssignment;
use App\Models\User;

class AssignTaskAction
{
    public function execute(Task $task, User $user): TaskAssignment
    {
        $existing = TaskAssignment::where('task_id', $task->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($existing) {
            throw new \DomainException('User is already assigned to this task.');
        }

        return TaskAssignment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);
    }
}
