<?php

namespace App\Domain\TimeTracking\Actions;

use App\Domain\TimeTracking\Models\TimeLog;
use App\Models\User;
use Illuminate\Support\Carbon;

class ClockOutAction
{
    public function execute(User $user): TimeLog
    {
        $openLog = TimeLog::forUser($user->id)
            ->whereNull('clock_out')
            ->latest('clock_in')
            ->first();

        if ($openLog === null) {
            throw new \DomainException('Cannot clock out: no open time log found.');
        }

        $openLog->update(['clock_out' => Carbon::now()]);

        return $openLog;
    }
}
