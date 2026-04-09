<?php

namespace Tests\Feature;

use App\Domain\TimeTracking\Actions\ClockInAction;
use App\Domain\TimeTracking\Actions\ClockOutAction;
use App\Domain\TimeTracking\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_clock_in(): void
    {
        $action = new ClockInAction();
        $log = $action->execute($this->user);

        $this->assertDatabaseHas('time_logs', [
            'id' => $log->id,
            'user_id' => $this->user->id,
        ]);
        $this->assertNotNull($log->clock_in);
        $this->assertNull($log->clock_out);
    }

    public function test_user_cannot_clock_in_twice(): void
    {
        $action = new ClockInAction();
        $action->execute($this->user);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot clock in: you already have an open time log.');

        $action->execute($this->user);
    }

    public function test_user_can_clock_out(): void
    {
        $clockIn = new ClockInAction();
        $clockIn->execute($this->user);

        $clockOut = new ClockOutAction();
        $log = $clockOut->execute($this->user);

        $this->assertNotNull($log->clock_out);
    }

    public function test_user_cannot_clock_out_without_open_session(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot clock out: no open time log found.');

        $action = new ClockOutAction();
        $action->execute($this->user);
    }

    public function test_duration_is_null_when_not_clocked_out(): void
    {
        $log = TimeLog::create([
            'user_id' => $this->user->id,
            'clock_in' => now(),
        ]);

        $this->assertNull($log->duration);
    }

    public function test_duration_is_computed_in_hours(): void
    {
        $log = TimeLog::create([
            'user_id' => $this->user->id,
            'clock_in' => now()->subMinutes(90),
            'clock_out' => now(),
        ]);

        $this->assertEqualsWithDelta(1.5, $log->duration, 0.02);
    }

    public function test_scope_for_user_filters_correctly(): void
    {
        $otherUser = User::factory()->create();

        TimeLog::create(['user_id' => $this->user->id, 'clock_in' => now()]);
        TimeLog::create(['user_id' => $otherUser->id, 'clock_in' => now()]);

        $this->assertCount(1, TimeLog::forUser($this->user->id)->get());
        $this->assertCount(1, TimeLog::forUser($otherUser->id)->get());
    }
}
