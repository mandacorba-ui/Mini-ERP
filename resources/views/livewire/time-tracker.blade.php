<?php

use App\Domain\TimeTracking\Actions\ClockInAction;
use App\Domain\TimeTracking\Actions\ClockOutAction;
use App\Domain\TimeTracking\Models\TimeLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public bool $isClockedIn = false;
    public ?string $sessionStart = null;
    public ?string $statusMessage = null;

    public function mount(): void
    {
        $this->refreshStatus();
    }

    public function clockIn(): void
    {
        try {
            $action = new ClockInAction();
            $action->execute(Auth::user());
            $this->statusMessage = 'Clocked in successfully.';
        } catch (\DomainException $e) {
            $this->statusMessage = $e->getMessage();
        }

        $this->refreshStatus();
    }

    public function clockOut(): void
    {
        try {
            $action = new ClockOutAction();
            $action->execute(Auth::user());
            $this->statusMessage = 'Clocked out successfully.';
        } catch (\DomainException $e) {
            $this->statusMessage = $e->getMessage();
        }

        $this->refreshStatus();
    }

    private function refreshStatus(): void
    {
        $openLog = TimeLog::forUser(Auth::id())
            ->whereNull('clock_out')
            ->latest('clock_in')
            ->first();

        $this->isClockedIn = $openLog !== null;
        $this->sessionStart = $openLog?->clock_in->format('H:i:s');
    }
}; ?>

<div>
    <h1 class="text-2xl font-semibold mb-6">Time Tracker</h1>

    @if ($statusMessage)
        <div class="mb-4 p-3 rounded bg-blue-100 text-blue-800 text-sm">
            {{ $statusMessage }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <div class="mb-4">
            <span class="font-medium">Status:</span>
            @if ($isClockedIn)
                <span class="text-green-600 font-semibold">Clocked In</span>
                <span class="text-gray-500 ml-2">since {{ $sessionStart }}</span>
            @else
                <span class="text-gray-500 font-semibold">Clocked Out</span>
            @endif
        </div>

        <div class="flex gap-3">
            <button
                wire:click="clockIn"
                @if($isClockedIn) disabled @endif
                class="px-4 py-2 rounded text-white font-medium
                    {{ $isClockedIn ? 'bg-gray-300 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700' }}"
            >
                Clock In
            </button>

            <button
                wire:click="clockOut"
                @if(!$isClockedIn) disabled @endif
                class="px-4 py-2 rounded text-white font-medium
                    {{ !$isClockedIn ? 'bg-gray-300 cursor-not-allowed' : 'bg-red-600 hover:bg-red-700' }}"
            >
                Clock Out
            </button>
        </div>
    </div>
</div>
