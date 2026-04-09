<?php

namespace App\Domain\WorkforcePlanning\Enums;

enum ShiftLabel: string
{
    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Night = 'night';
}
