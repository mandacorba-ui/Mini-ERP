<?php

namespace App\Domain\TaskManagement\Enums;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case Done = 'done';

    private const array FORWARD_ORDER = [
        'todo' => 0,
        'in_progress' => 1,
        'in_review' => 2,
        'done' => 3,
    ];

    public function canTransitionTo(self $next): bool
    {
        return self::FORWARD_ORDER[$next->value] > self::FORWARD_ORDER[$this->value];
    }
}
