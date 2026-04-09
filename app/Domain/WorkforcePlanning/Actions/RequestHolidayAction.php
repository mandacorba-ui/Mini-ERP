<?php

namespace App\Domain\WorkforcePlanning\Actions;

use App\Domain\WorkforcePlanning\Enums\HolidayStatus;
use App\Domain\WorkforcePlanning\Enums\LeaveType;
use App\Domain\WorkforcePlanning\Models\Holiday;
use App\Domain\WorkforcePlanning\Services\OverlapDetectionService;
use App\Models\User;
use Illuminate\Support\Carbon;

class RequestHolidayAction
{
    public function __construct(
        private OverlapDetectionService $overlapService,
    ) {}

    public function execute(
        User $employee,
        Carbon $startDate,
        Carbon $endDate,
        LeaveType $type,
    ): Holiday {
        if ($startDate->gt($endDate)) {
            throw new \DomainException('Start date must be before or equal to end date.');
        }

        $this->ensureNoOverlapWithApprovedHolidays($employee->id, $startDate, $endDate);

        return Holiday::create([
            'user_id' => $employee->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'type' => $type,
            'status' => HolidayStatus::Pending,
        ]);
    }

    private function ensureNoOverlapWithApprovedHolidays(string $userId, Carbon $startDate, Carbon $endDate): void
    {
        $approvedHolidays = Holiday::forUser($userId)->approved()->get();

        foreach ($approvedHolidays as $holiday) {
            if ($this->overlapService->hasOverlap($startDate, $endDate, $holiday->start_date, $holiday->end_date)) {
                throw new \DomainException('Holiday request overlaps with an existing approved holiday.');
            }
        }
    }
}
