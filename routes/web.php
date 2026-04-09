<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');

Route::middleware('auth')->group(function () {
    Volt::route('/time-tracking', 'time-tracker')->name('time-tracking');
});

// Dev-only login helper — auto-login as admin for quick testing
Route::get('/dev-login', function () {
    abort_unless(app()->environment('local'), 403);
    Auth::login(User::where('email', 'admin@example.com')->firstOrFail());
    return redirect()->route('dashboard');
})->name('dev-login');
