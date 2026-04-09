<?php

namespace App\Domain\WorkforcePlanning\Services;

use App\Domain\WorkforcePlanning\Models\Holiday;
use Illuminate\Support\Carbon;

class LeaveBalanceService
{
    public const int ANNUAL_ALLOWANCE = 20;

    /**
     * Count weekdays (Mon–Fri) between start and end date inclusive.
     */
    public function calculateRequestedDays(Carbon $startDate, Carbon $endDate): int
    {
        $days = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            if ($current->isWeekday()) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }

    /**
     * Get the number of approved leave days already used this year.
     */
    public function getUsedDays(string $userId, int $year): int
    {
        $holidays = Holiday::forUser($userId)
            ->approved()
            ->whereYear('start_date', $year)
            ->get();

        $total = 0;

        foreach ($holidays as $holiday) {
            $total += $this->calculateRequestedDays($holiday->start_date, $holiday->end_date);
        }

        return $total;
    }

    /**
     * Get remaining leave balance for a user in a given year.
     */
    public function getRemainingBalance(string $userId, int $year): int
    {
        return self::ANNUAL_ALLOWANCE - $this->getUsedDays($userId, $year);
    }
}
