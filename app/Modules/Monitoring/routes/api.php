<?php

use App\Modules\Monitoring\Controllers\MonitoringController;
use Illuminate\Support\Facades\Route;

Route::prefix('monitoring')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/health', [MonitoringController::class, 'health'])
        ->name('monitoring.health')
        ->middleware('permission:monitoring.view');

    Route::get('/metrics', [MonitoringController::class, 'metrics'])
        ->name('monitoring.metrics')
        ->middleware('permission:monitoring.view');

    Route::get('/logs', [MonitoringController::class, 'logs'])
        ->name('monitoring.logs')
        ->middleware('permission:monitoring.view');

    Route::get('/queue-status', [MonitoringController::class, 'queueStatus'])
        ->name('monitoring.queue-status')
        ->middleware('permission:monitoring.view');

    Route::get('/database-status', [MonitoringController::class, 'databaseStatus'])
        ->name('monitoring.database-status')
        ->middleware('permission:monitoring.view');
});

