<?php

use App\Modules\Inventory\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventory')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/statistics', [InventoryController::class, 'statistics'])
        ->name('inventory.statistics')
        ->middleware('permission:inventory.view');

    Route::get('/low-stock', [InventoryController::class, 'lowStock'])
        ->name('inventory.low-stock')
        ->middleware('permission:inventory.view');

    Route::apiResource('products', InventoryController::class)
        ->except(['update'])
        ->middleware('permission:inventory.manage');

    Route::patch('/products/{id}/stock', [InventoryController::class, 'updateStock'])
        ->name('inventory.products.update-stock')
        ->middleware('permission:inventory.update');
});

