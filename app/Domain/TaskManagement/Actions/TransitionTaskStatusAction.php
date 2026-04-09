<?php

namespace App\Domain\TaskManagement\Actions;

use App\Domain\TaskManagement\Enums\TaskStatus;
use App\Domain\TaskManagement\Models\Task;

class TransitionTaskStatusAction
{
    public function execute(Task $task, TaskStatus $newStatus): Task
    {
        if (! $task->status->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Cannot transition from '{$task->status->value}' to '{$newStatus->value}'. Only forward transitions are allowed."
            );
        }

        $task->update(['status' => $newStatus]);

        return $task;
    }
}
