<?php

use App\Http\Controllers\Warehouse\BatchController;
use App\Http\Controllers\Warehouse\ChannelController;
use App\Http\Controllers\Warehouse\ExpiryController;
use App\Http\Controllers\Warehouse\ReplenishmentController;
use App\Http\Controllers\Warehouse\StockController;
use App\Http\Controllers\Warehouse\StockCountController;
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
        Route::get('productos/{product}/lotes', [BatchController::class, 'index'])->name('batches.index');
        Route::get('caducidades', [ExpiryController::class, 'index'])->name('expiry.index');
    });

    Route::middleware('can:stock.entry')->group(function () {
        Route::patch('productos/{product}/minimo', [ReplenishmentController::class, 'updateMinimum'])->name('minimum.update');
        Route::get('entradas/nueva', [StockEntryController::class, 'create'])->name('entries.create');
        Route::post('entradas', [StockEntryController::class, 'store'])->name('entries.store');
    });

    Route::middleware('can:stock.exit')->group(function () {
        Route::get('salidas/nueva', [StockExitController::class, 'create'])->name('exits.create');
        Route::post('salidas', [StockExitController::class, 'store'])->name('exits.store');
        Route::post('caducidades/bloquear', [ExpiryController::class, 'block'])->name('expiry.block');
        Route::patch('lotes/{batch}/retirada', [ExpiryController::class, 'withdraw'])->name('expiry.withdraw');
    });

    Route::middleware('can:stock.adjust')->group(function () {
        Route::get('recuentos/nuevo', [StockCountController::class, 'create'])->name('counts.create');
        Route::post('recuentos', [StockCountController::class, 'store'])->name('counts.store');
    });

    Route::middleware('can:stock.transfer')->group(function () {
        Route::get('canales', [ChannelController::class, 'create'])->name('transfers.create');
        Route::post('canales/transferencias', [ChannelController::class, 'transfer'])->name('transfers.store');
    });

    Route::middleware('can:stock.channels')->group(function () {
        Route::put('canales/reparto', [ChannelController::class, 'updateShares'])->name('channels.shares');
        Route::post('canales/reparto/aplicar', [ChannelController::class, 'rebalance'])->name('channels.rebalance');
    });
});
