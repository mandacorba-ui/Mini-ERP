<?php

namespace App\Domain\WorkforcePlanning\Enums;

enum LeaveType: string
{
    case Annual = 'annual';
    case Sick = 'sick';
    case Personal = 'personal';
}
