<?php

use App\Modules\Sales\Controllers\SalesController;
use Illuminate\Support\Facades\Route;

Route::prefix('sales')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/statistics', [SalesController::class, 'statistics'])
        ->name('sales.statistics')
        ->middleware('permission:sales.view');

    Route::apiResource('orders', SalesController::class)
        ->middleware('permission:sales.manage');
});

