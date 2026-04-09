@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">Dashboard</h1>

    @auth
        <p class="mb-4 text-gray-600">Welcome, {{ Auth::user()->name }}.</p>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="font-medium mb-3">Quick Links</h2>
            <ul class="list-disc list-inside space-y-1">
                <li><a href="{{ route('time-tracking') }}" class="text-blue-600 hover:underline">Time Tracking</a></li>
            </ul>
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-6">
            <p class="mb-3 text-gray-600">You are not logged in.</p>
            @if (app()->environment('local'))
                <a href="{{ route('dev-login') }}" class="text-blue-600 hover:underline">Dev Login (admin)</a>
            @endif
        </div>
    @endauth
@endsection
