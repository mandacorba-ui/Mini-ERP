<?php

use App\Domain\IdentityAndAccess\Enums\UserRole;
use App\Domain\TimeTracking\Models\TimeLog;
use App\Domain\WorkforcePlanning\Actions\ApproveHolidayAction;
use App\Domain\WorkforcePlanning\Actions\RejectHolidayAction;
use App\Domain\WorkforcePlanning\Models\Holiday;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! in_array($user->role, [UserRole::Manager, UserRole::Admin], true)) {
            abort(403);
        }
    }

    public function approve(string $holidayId): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        try {
            $holiday = Holiday::findOrFail($holidayId);
            $action = app(ApproveHolidayAction::class);
            $action->execute($holiday, Auth::user());
            $this->successMessage = 'Holiday approved.';
        } catch (\DomainException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function reject(string $holidayId): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        try {
            $holiday = Holiday::findOrFail($holidayId);
            $action = new RejectHolidayAction();
            $action->execute($holiday);
            $this->successMessage = 'Holiday rejected.';
        } catch (\DomainException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function with(): array
    {
        $pendingHolidays = Holiday::pending()
            ->with('employee')
            ->orderBy('start_date')
            ->get();

        // Team weekly hours summary
        $employees = User::where('role', UserRole::Employee)->get();
        $weeklyHours = $employees->map(function ($emp) {
            $hours = TimeLog::forUser($emp->id)
                ->thisWeek()
                ->whereNotNull('clock_out')
                ->get()
                ->sum(fn ($log) => $log->duration ?? 0);

            return [
                'name' => $emp->name,
                'hours' => round($hours, 1),
            ];
        });

        return [
            'pendingHolidays' => $pendingHolidays,
            'weeklyHours' => $weeklyHours,
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Holiday Approvals') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($successMessage)
                <div class="p-3 bg-green-100 text-green-700 rounded">{{ $successMessage }}</div>
            @endif
            @if ($errorMessage)
                <div class="p-3 bg-red-100 text-red-700 rounded">{{ $errorMessage }}</div>
            @endif

            {{-- Pending Requests --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Pending Holiday Requests</h3>

                @if ($pendingHolidays->isEmpty())
                    <p class="text-sm text-gray-500">No pending requests.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Comment</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($pendingHolidays as $holiday)
                                <tr>
                                    <td class="px-4 py-2 text-sm font-medium">{{ $holiday->employee->name }}</td>
                                    <td class="px-4 py-2 text-sm">{{ $holiday->start_date->format('M j') }} – {{ $holiday->end_date->format('M j, Y') }}</td>
                                    <td class="px-4 py-2 text-sm capitalize">{{ $holiday->type->value }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-500">{{ $holiday->comment ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm space-x-2">
                                        <button wire:click="approve('{{ $holiday->id }}')"
                                            class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-500 transition">
                                            Approve
                                        </button>
                                        <button wire:click="reject('{{ $holiday->id }}')"
                                            class="px-3 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-500 transition">
                                            Reject
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Team Weekly Hours --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Team Hours This Week</h3>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Hours</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($weeklyHours as $entry)
                            <tr>
                                <td class="px-4 py-2 text-sm">{{ $entry['name'] }}</td>
                                <td class="px-4 py-2 text-sm font-medium">{{ number_format($entry['hours'], 1) }}h</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
