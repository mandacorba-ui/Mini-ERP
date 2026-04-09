<?php

namespace App\Domain\WorkforcePlanning\Services;

use Illuminate\Support\Carbon;

class OverlapDetectionService
{
    /**
     * Two ranges overlap if: start_a < end_b AND start_b < end_a
     */
    public function hasOverlap(
        Carbon $startA,
        Carbon $endA,
        Carbon $startB,
        Carbon $endB,
    ): bool {
        return $startA->lt($endB) && $startB->lt($endA);
    }
}
