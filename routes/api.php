<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CircuitBreakerController;

// Circuit Breaker Management
Route::prefix('sre')->group(function () {
    Route::get('/circuit-breakers', [CircuitBreakerController::class, 'index']);
    Route::get('/circuit-breakers/{service}', [CircuitBreakerController::class, 'show']);
    Route::post('/circuit-breakers/{service}/reset', [CircuitBreakerController::class, 'reset']);
});

// Module routes are loaded via ModuleServiceProvider
