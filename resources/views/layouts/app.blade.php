<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Mini ERP') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    @endif
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen font-sans antialiased">
    <nav class="bg-white shadow mb-6">
        <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ url('/') }}" class="font-bold text-lg">Mini ERP</a>
            <div class="flex items-center gap-4 text-sm">
                @auth
                    <a href="{{ route('time-tracking') }}" class="hover:underline">Time Tracking</a>
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-600">{{ Auth::user()->name }}</span>
                @endauth
            </div>
        </div>
    </nav>
    <main class="max-w-2xl mx-auto py-6 px-4">
        {{ $slot ?? '' }}
        @yield('content')
    </main>
    @livewireScripts
</body>
</html>
