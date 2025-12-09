<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IntegrationMonitorController;
use App\Http\Controllers\MappingController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});

// Protected routes - require authentication
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/integration-monitor', [IntegrationMonitorController::class, 'index'])->name('integration-monitor');
    Route::get('/mapping-editor', [MappingController::class, 'index'])->name('mapping-editor');
});

// Settings (Super Admin only)
Route::middleware(['role:Super Admin'])->group(function () {
    // Website Settings
    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings');
    Route::get('/settings/website', [\App\Http\Controllers\SettingsController::class, 'website'])->name('settings.website');
    Route::post('/settings/website', [\App\Http\Controllers\SettingsController::class, 'update'])->name('settings.website.update');
    
    // User Settings (CRUD)
    Route::get('/settings/users', [\App\Http\Controllers\UserController::class, 'index'])->name('settings.users');
    Route::post('/settings/users', [\App\Http\Controllers\UserController::class, 'store'])->name('settings.users.store');
    Route::put('/settings/users/{user}', [\App\Http\Controllers\UserController::class, 'update'])->name('settings.users.update');
    Route::delete('/settings/users/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('settings.users.destroy');
});

// Authentication routes
Route::get('/login', function () {
    return Inertia::render('Login');
})->name('login');

Route::post('/login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        
        return redirect()->intended('/dashboard');
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
})->name('login.post');

Route::get('/register', function () {
    return Inertia::render('Register');
})->name('register');

Route::post('/logout', function (\Illuminate\Http\Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->name('logout');

