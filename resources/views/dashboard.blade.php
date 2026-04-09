<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4">Welcome, <strong>{{ Auth::user()->name }}</strong>! Role: <span class="capitalize">{{ Auth::user()->role->value }}</span></p>

                    <h3 class="text-lg font-medium mb-3">Quick Links</h3>
                    <ul class="list-disc list-inside space-y-1">
                        <li><a href="{{ route('time-tracking') }}" class="text-blue-600 hover:underline">Time Tracking</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
