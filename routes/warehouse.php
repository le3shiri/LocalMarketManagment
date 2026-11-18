<?php

use App\Http\Controllers\Warehouse\ReplenishmentController;
use App\Http\Controllers\Warehouse\StockController;
use App\Http\Controllers\Warehouse\StockEntryController;
use App\Http\Controllers\Warehouse\StockExitController;
use App\Http\Controllers\Warehouse\StockMovementController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('almacen')->name('warehouse.')->group(function () {
    Route::middleware('can:stock.view')->group(function () {
        Route::get('inventario', [StockController::class, 'index'])->name('stock.index');
        Route::get('movimientos', [StockMovementController::class, 'index'])->name('movements.index');
        Route::get('movimientos/exportar', [StockMovementController::class, 'export'])->name('movements.export');
        Route::get('reposicion', [ReplenishmentController::class, 'index'])->name('replenishment.index');
    });

    Route::middleware('can:stock.entry')->group(function () {
        Route::patch('productos/{product}/minimo', [ReplenishmentController::class, 'updateMinimum'])->name('minimum.update');
        Route::get('entradas/nueva', [StockEntryController::class, 'create'])->name('entries.create');
        Route::post('entradas', [StockEntryController::class, 'store'])->name('entries.store');
    });

    Route::middleware('can:stock.exit')->group(function () {
        Route::get('salidas/nueva', [StockExitController::class, 'create'])->name('exits.create');
        Route::post('salidas', [StockExitController::class, 'store'])->name('exits.store');
    });
});
