<?php

namespace App\Domain\WorkforcePlanning\Actions;

use App\Domain\WorkforcePlanning\Enums\HolidayStatus;
use App\Domain\WorkforcePlanning\Models\Holiday;

class RejectHolidayAction
{
    public function execute(Holiday $holiday, ?string $comment = null): Holiday
    {
        if ($holiday->status !== HolidayStatus::Pending) {
            throw new \DomainException('Only pending holidays can be rejected.');
        }

        $holiday->update([
            'status' => HolidayStatus::Rejected,
            'comment' => $comment,
        ]);

        return $holiday;
    }
}
