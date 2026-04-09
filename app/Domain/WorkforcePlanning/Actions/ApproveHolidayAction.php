<?php

namespace App\Domain\WorkforcePlanning\Actions;

use App\Domain\IdentityAndAccess\Enums\UserRole;
use App\Domain\WorkforcePlanning\Enums\HolidayStatus;
use App\Domain\WorkforcePlanning\Models\Holiday;
use App\Domain\WorkforcePlanning\Services\LeaveBalanceService;
use App\Domain\WorkforcePlanning\Services\OverlapDetectionService;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApproveHolidayAction
{
    public function __construct(
        private OverlapDetectionService $overlapService,
        private LeaveBalanceService $leaveBalanceService,
    ) {}

    public function execute(Holiday $holiday, User $approver): Holiday
    {
        if ($holiday->status !== HolidayStatus::Pending) {
            throw new \DomainException('Only pending holidays can be approved.');
        }

        if (! in_array($approver->role, [UserRole::Manager, UserRole::Admin], true)) {
            throw new \DomainException('Only managers or admins can approve holiday requests.');
        }

        if ($approver->id === $holiday->user_id) {
            throw new \DomainException('You cannot approve your own holiday request.');
        }

        $this->ensureNoOverlapWithApprovedHolidays($holiday);
        $this->ensureSufficientBalance($holiday);

        return DB::transaction(function () use ($holiday, $approver) {
            $holiday->update([
                'status' => HolidayStatus::Approved,
                'approver_id' => $approver->id,
            ]);

            return $holiday;
        });
    }

    private function ensureNoOverlapWithApprovedHolidays(Holiday $holiday): void
    {
        $approvedHolidays = Holiday::forUser($holiday->user_id)
            ->approved()
            ->where('id', '!=', $holiday->id)
            ->get();

        foreach ($approvedHolidays as $existing) {
            if ($this->overlapService->hasOverlap(
                $holiday->start_date,
                $holiday->end_date,
                $existing->start_date,
                $existing->end_date,
            )) {
                throw new \DomainException('Holiday overlaps with an existing approved holiday.');
            }
        }
    }

    private function ensureSufficientBalance(Holiday $holiday): void
    {
        $requestedDays = $this->leaveBalanceService->calculateRequestedDays(
            $holiday->start_date,
            $holiday->end_date,
        );

        $remaining = $this->leaveBalanceService->getRemainingBalance(
            $holiday->user_id,
            $holiday->start_date->year,
        );

        if ($requestedDays > $remaining) {
            throw new \DomainException(
                "Insufficient leave balance. Requested: {$requestedDays} days, remaining: {$remaining} days."
            );
        }
    }
}
