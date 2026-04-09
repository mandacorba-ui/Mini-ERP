<?php

use App\Domain\IdentityAndAccess\Enums\UserRole;
use App\Domain\TaskManagement\Models\Task;
use App\Domain\TaskManagement\Models\TaskAssignment;
use App\Domain\TimeTracking\Models\TimeLog;
use App\Domain\WorkforcePlanning\Models\Holiday;
use App\Domain\WorkforcePlanning\Models\ShiftAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public float $weeklyHours = 0;
    public int $pendingHolidayCount = 0;
    public bool $isManager = false;
    public array $upcomingShifts = [];
    public array $overdueTasks = [];

    public function mount(): void
    {
        $user = Auth::user();

        // A. Weekly Hours
        $this->weeklyHours = TimeLog::forUser($user->id)
            ->thisWeek()
            ->whereNotNull('clock_out')
            ->get()
            ->sum(fn ($log) => $log->duration ?? 0);

        // B. Pending Holiday Requests (managers only)
        $this->isManager = in_array($user->role, [UserRole::Manager, UserRole::Admin]);
        if ($this->isManager) {
            $this->pendingHolidayCount = Holiday::pending()->count();
        }

        // C. Upcoming Shifts (next 7 days)
        $today = Carbon::today();
        $this->upcomingShifts = ShiftAssignment::where('user_id', $user->id)
            ->whereHas('shift', function ($q) use ($today) {
                $q->whereBetween('date', [$today->toDateString(), $today->copy()->addDays(7)->toDateString()]);
            })
            ->with('shift')
            ->get()
            ->sortBy(fn ($a) => $a->shift->date)
            ->map(fn ($a) => [
                'date' => $a->shift->date->format('D, M j'),
                'label' => $a->shift->label->value,
                'time' => $a->shift->start_time . ' – ' . $a->shift->end_time,
            ])
            ->values()
            ->toArray();

        // D. Overdue Tasks (assigned to user)
        $assignedTaskIds = TaskAssignment::where('user_id', $user->id)->pluck('task_id');
        $this->overdueTasks = Task::overdue()
            ->whereIn('id', $assignedTaskIds)
            ->orderBy('due_date')
            ->get()
            ->map(fn ($t) => [
                'title' => $t->title,
                'due_date' => $t->due_date->format('M j, Y'),
                'status' => $t->status->value,
                'priority' => $t->priority->value,
            ])
            ->toArray();
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Grid layout --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- A. Weekly Hours --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Weekly Hours</h3>
                    <p class="text-3xl font-bold text-blue-600">{{ number_format($weeklyHours, 1) }}h</p>
                    <p class="text-sm text-gray-500 mt-1">Hours worked this week</p>
                </div>

                {{-- B. Pending Holiday Requests (managers/admins only) --}}
                @if ($isManager)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Pending Holiday Requests</h3>
                    <p class="text-3xl font-bold {{ $pendingHolidayCount > 0 ? 'text-amber-600' : 'text-green-600' }}">
                        {{ $pendingHolidayCount }}
                    </p>
                    <p class="text-sm text-gray-500 mt-1">Awaiting approval</p>
                </div>
                @endif

                {{-- C. Upcoming Shifts --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Upcoming Shifts</h3>
                    @if (count($upcomingShifts) > 0)
                        <ul class="divide-y divide-gray-200">
                            @foreach ($upcomingShifts as $shift)
                                <li class="py-2 flex justify-between items-center">
                                    <div>
                                        <span class="font-medium">{{ $shift['date'] }}</span>
                                        <span class="ml-2 text-sm text-gray-500 capitalize">{{ $shift['label'] }}</span>
                                    </div>
                                    <span class="text-sm text-gray-600">{{ $shift['time'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500">No shifts in the next 7 days.</p>
                    @endif
                </div>

                {{-- D. Overdue Tasks --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Overdue Tasks</h3>
                    @if (count($overdueTasks) > 0)
                        <ul class="divide-y divide-gray-200">
                            @foreach ($overdueTasks as $task)
                                <li class="py-2">
                                    <div class="flex justify-between items-center">
                                        <span class="font-medium">{{ $task['title'] }}</span>
                                        <span class="text-xs px-2 py-1 rounded capitalize
                                            {{ $task['priority'] === 'high' ? 'bg-red-100 text-red-700' : ($task['priority'] === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
                                            {{ $task['priority'] }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-red-500 mt-1">Due: {{ $task['due_date'] }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500">No overdue tasks.</p>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>
