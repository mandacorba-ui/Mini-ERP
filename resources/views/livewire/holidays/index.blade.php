<?php

use App\Domain\WorkforcePlanning\Actions\RequestHolidayAction;
use App\Domain\WorkforcePlanning\Enums\HolidayStatus;
use App\Domain\WorkforcePlanning\Enums\LeaveType;
use App\Domain\WorkforcePlanning\Models\Holiday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $start_date = '';
    public string $end_date = '';
    public string $type = 'annual';
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function submit(): void
    {
        $this->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'type' => ['required', 'in:annual,sick,personal'],
        ]);

        $this->successMessage = null;
        $this->errorMessage = null;

        try {
            $action = app(RequestHolidayAction::class);
            $action->execute(
                Auth::user(),
                Carbon::parse($this->start_date),
                Carbon::parse($this->end_date),
                LeaveType::from($this->type),
            );

            $this->successMessage = 'Holiday request submitted successfully.';
            $this->reset(['start_date', 'end_date', 'type']);
            $this->type = 'annual';
        } catch (\DomainException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function with(): array
    {
        return [
            'holidays' => Holiday::forUser(Auth::id())
                ->orderByDesc('start_date')
                ->get(),
            'leaveTypes' => LeaveType::cases(),
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Holidays') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Request Form --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Request Holiday</h3>

                @if ($successMessage)
                    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">{{ $successMessage }}</div>
                @endif
                @if ($errorMessage)
                    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded">{{ $errorMessage }}</div>
                @endif

                <form wire:submit="submit" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" wire:model="start_date" id="start_date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('start_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" wire:model="end_date" id="end_date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('end_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                            <select wire:model="type" id="type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @foreach ($leaveTypes as $lt)
                                    <option value="{{ $lt->value }}">{{ ucfirst($lt->value) }}</option>
                                @endforeach
                            </select>
                            @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 transition">
                        Submit Request
                    </button>
                </form>
            </div>

            {{-- History --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">My Holiday History</h3>

                @if ($holidays->isEmpty())
                    <p class="text-sm text-gray-500">No holiday requests yet.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Comment</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($holidays as $holiday)
                                <tr>
                                    <td class="px-4 py-2 text-sm">{{ $holiday->start_date->format('M j') }} – {{ $holiday->end_date->format('M j, Y') }}</td>
                                    <td class="px-4 py-2 text-sm capitalize">{{ $holiday->type->value }}</td>
                                    <td class="px-4 py-2 text-sm">
                                        <span class="px-2 py-1 text-xs rounded
                                            {{ $holiday->status === HolidayStatus::Approved ? 'bg-green-100 text-green-700' : '' }}
                                            {{ $holiday->status === HolidayStatus::Pending ? 'bg-yellow-100 text-yellow-700' : '' }}
                                            {{ $holiday->status === HolidayStatus::Rejected ? 'bg-red-100 text-red-700' : '' }}">
                                            {{ ucfirst($holiday->status->value) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-500">{{ $holiday->comment ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

        </div>
    </div>
</div>
