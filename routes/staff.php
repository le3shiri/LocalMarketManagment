<?php

use App\Http\Controllers\Staff\OrderController;
use Illuminate\Support\Facades\Route;

/*
| Shop staff: the daily orders panel for every channel (web, phone, WhatsApp).
*/
Route::middleware(['auth', 'can:orders.manage'])->prefix('gestion')->name('staff.')->group(function () {
    Route::get('pedidos', [OrderController::class, 'index'])->name('orders.index');
    Route::get('pedidos/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('pedidos/{order}/estado', [OrderController::class, 'advance'])->name('orders.advance');
});
