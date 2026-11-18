<?php

use App\Http\Controllers\Warehouse\StockController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('almacen')->name('warehouse.')->group(function () {
    Route::get('inventario', [StockController::class, 'index'])->middleware('can:stock.view')->name('stock.index');
});
