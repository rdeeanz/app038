<?php

use App\Modules\ERPIntegration\Controllers\ERPIntegrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('erp-integration')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [ERPIntegrationController::class, 'index'])
        ->name('erp-integration.index')
        ->middleware('permission:erp-integration.view');

    Route::post('/sync', [ERPIntegrationController::class, 'sync'])
        ->name('erp-integration.sync')
        ->middleware('permission:erp-integration.sync');

    Route::get('/sync/{syncId}/status', [ERPIntegrationController::class, 'syncStatus'])
        ->name('erp-integration.sync-status')
        ->middleware('permission:erp-integration.view');

    Route::post('/test-connection', [ERPIntegrationController::class, 'testConnection'])
        ->name('erp-integration.test-connection')
        ->middleware('permission:erp-integration.manage');
});

