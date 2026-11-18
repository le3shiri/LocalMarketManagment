<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Livewire\CartPage;
use App\Livewire\Checkout;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalogo/{product}', [CatalogController::class, 'show'])->name('catalog.show');

Route::middleware('auth')->group(function () {
    Route::get('/cuenta', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/cuenta', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/cuenta/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/avisos', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/avisos/leidos', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/avisos/{id}', [NotificationController::class, 'show'])->name('notifications.show');

    Route::middleware('can:cart.use')->group(function () {
        Route::get('/carrito', CartPage::class)->name('cart.index');
        Route::get('/checkout', Checkout::class)->name('checkout.index');
        Route::get('/pedidos', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/pedidos/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/pedidos/{order}/cancelar', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/pedidos/{order}/repetir', [OrderController::class, 'repeat'])->name('orders.repeat');
    });
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/warehouse.php';
require __DIR__.'/staff.php';
require __DIR__.'/pos.php';
