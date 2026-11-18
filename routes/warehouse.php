<?php

use App\Http\Controllers\Warehouse\StockController;
use App\Http\Controllers\Warehouse\StockEntryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('almacen')->name('warehouse.')->group(function () {
    Route::get('inventario', [StockController::class, 'index'])->middleware('can:stock.view')->name('stock.index');

    Route::middleware('can:stock.entry')->group(function () {
        Route::get('entradas/nueva', [StockEntryController::class, 'create'])->name('entries.create');
        Route::post('entradas', [StockEntryController::class, 'store'])->name('entries.store');
    });
});
