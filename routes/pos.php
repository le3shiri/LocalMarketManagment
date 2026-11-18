<?php

use App\Http\Controllers\Pos\RefundController;
use App\Http\Controllers\Pos\TicketController;
use App\Livewire\Pos\Terminal;
use Illuminate\Support\Facades\Route;

/*
| Counter sales (TPV / POS).
*/
Route::middleware('auth')->prefix('caja')->name('pos.')->group(function () {
    Route::middleware('can:pos.use')->group(function () {
        Route::get('/', Terminal::class)->name('terminal');
        Route::get('tickets/{sale}', [TicketController::class, 'show'])->name('tickets.show');
    });

    Route::middleware('can:refunds.manage')->group(function () {
        Route::get('tickets/{sale}/devolucion', [RefundController::class, 'create'])->name('refunds.create');
        Route::post('tickets/{sale}/devolucion', [RefundController::class, 'store'])->name('refunds.store');
        Route::get('devoluciones/{refund}', [RefundController::class, 'show'])->name('refunds.show');
    });
});
