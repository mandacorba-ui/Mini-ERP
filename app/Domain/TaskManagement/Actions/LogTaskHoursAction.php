<?php

namespace App\Domain\TaskManagement\Actions;

use App\Domain\TaskManagement\Models\TaskAssignment;
use App\Models\User;

class LogTaskHoursAction
{
    public function execute(TaskAssignment $assignment, int $hours): TaskAssignment
    {
        if ($hours <= 0) {
            throw new \DomainException('Logged hours must be a positive number.');
        }

        $assignment->update([
            'logged_hours' => $assignment->logged_hours + $hours,
        ]);

        return $assignment;
    }
}
