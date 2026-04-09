<?php

namespace Tests\Unit;

use App\Domain\WorkforcePlanning\Services\OverlapDetectionService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OverlapDetectionTest extends TestCase
{
    private OverlapDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OverlapDetectionService();
    }

    public function test_overlapping_ranges_detected(): void
    {
        $this->assertTrue($this->service->hasOverlap(
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-10'),
            Carbon::parse('2026-06-05'),
            Carbon::parse('2026-06-15'),
        ));
    }

    public function test_contained_range_detected(): void
    {
        $this->assertTrue($this->service->hasOverlap(
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-20'),
            Carbon::parse('2026-06-05'),
            Carbon::parse('2026-06-10'),
        ));
    }

    public function test_non_overlapping_ranges_not_detected(): void
    {
        $this->assertFalse($this->service->hasOverlap(
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-05'),
            Carbon::parse('2026-06-10'),
            Carbon::parse('2026-06-15'),
        ));
    }

    public function test_adjacent_ranges_do_not_overlap(): void
    {
        // end_a == start_b → no overlap with strict < comparison
        $this->assertFalse($this->service->hasOverlap(
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-05'),
            Carbon::parse('2026-06-05'),
            Carbon::parse('2026-06-10'),
        ));
    }

    public function test_identical_ranges_overlap(): void
    {
        $this->assertTrue($this->service->hasOverlap(
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-05'),
            Carbon::parse('2026-06-01'),
            Carbon::parse('2026-06-05'),
        ));
    }
}
