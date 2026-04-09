<?php

use App\Http\Controllers\ProfileController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Volt::route('/time-tracking', 'time-tracker')->name('time-tracking');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Dev-only login helper — auto-login as admin for quick testing
Route::get('/dev-login', function () {
    abort_unless(app()->environment('local'), 403);
    Auth::login(User::where('email', 'admin@example.com')->firstOrFail());
    return redirect()->route('dashboard');
})->name('dev-login');

require __DIR__.'/auth.php';
