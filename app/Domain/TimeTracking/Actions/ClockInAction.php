<?php

namespace App\Domain\TimeTracking\Actions;

use App\Domain\TimeTracking\Models\TimeLog;
use App\Models\User;
use Illuminate\Support\Carbon;

class ClockInAction
{
    public function execute(User $user): TimeLog
    {
        $hasOpenSession = TimeLog::forUser($user->id)
            ->whereNull('clock_out')
            ->exists();

        if ($hasOpenSession) {
            throw new \DomainException('Cannot clock in: you already have an open time log. Please clock out first.');
        }

        return TimeLog::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now(),
        ]);
    }
}
