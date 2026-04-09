<?php

namespace App\Domain\WorkforcePlanning\Enums;

enum HolidayStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
