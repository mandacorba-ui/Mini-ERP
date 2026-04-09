<?php

use App\Domain\IdentityAndAccess\Enums\UserRole;
use App\Domain\WorkforcePlanning\Actions\AssignShiftAction;
use App\Domain\WorkforcePlanning\Models\Shift;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public ?string $selectedUserId = null;
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! in_array($user->role, [UserRole::Manager, UserRole::Admin], true)) {
            abort(403);
        }
    }

    public function assign(string $shiftId): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if (! $this->selectedUserId) {
            $this->errorMessage = 'Please select a user.';
            return;
        }

        try {
            $shift = Shift::findOrFail($shiftId);
            $user = User::findOrFail($this->selectedUserId);

            $action = app(AssignShiftAction::class);
            $action->execute($shift, $user);

            $this->successMessage = "{$user->name} assigned to shift on {$shift->date->format('Y-m-d')}.";
            $this->selectedUserId = null;
        } catch (\DomainException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function with(): array
    {
        $shifts = Shift::where('date', '>=', Carbon::today())
            ->where('date', '<=', Carbon::today()->addDays(7))
            ->with('assignments.user')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $users = User::orderBy('name')->get();

        return [
            'shifts' => $shifts,
            'users' => $users,
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Shift Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if ($successMessage)
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ $successMessage }}</div>
            @endif

            @if ($errorMessage)
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">{{ $errorMessage }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">Upcoming Shifts (Next 7 Days)</h3>

                    @if ($shifts->isEmpty())
                        <p class="text-gray-500">No upcoming shifts.</p>
                    @else
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-2 px-3">Date</th>
                                    <th class="py-2 px-3">Start</th>
                                    <th class="py-2 px-3">End</th>
                                    <th class="py-2 px-3">Label</th>
                                    <th class="py-2 px-3">Assigned</th>
                                    <th class="py-2 px-3">Assign User</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($shifts as $shift)
                                    <tr class="border-b">
                                        <td class="py-2 px-3">{{ $shift->date->format('Y-m-d') }}</td>
                                        <td class="py-2 px-3">{{ $shift->start_time }}</td>
                                        <td class="py-2 px-3">{{ $shift->end_time }}</td>
                                        <td class="py-2 px-3 capitalize">{{ $shift->label->value }}</td>
                                        <td class="py-2 px-3">
                                            @forelse ($shift->assignments as $assignment)
                                                <span class="inline-block bg-gray-100 rounded px-2 py-0.5 text-sm mr-1">{{ $assignment->user->name }}</span>
                                            @empty
                                                <span class="text-gray-400 text-sm">None</span>
                                            @endforelse
                                        </td>
                                        <td class="py-2 px-3">
                                            <div class="flex items-center gap-2">
                                                <select wire:model="selectedUserId" class="border-gray-300 rounded text-sm">
                                                    <option value="">Select user</option>
                                                    @foreach ($users as $user)
                                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button wire:click="assign('{{ $shift->id }}')" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">
                                                    Assign
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
