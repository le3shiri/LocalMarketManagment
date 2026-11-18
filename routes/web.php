<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ProfileController;
use App\Livewire\CartPage;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalogo/{product}', [CatalogController::class, 'show'])->name('catalog.show');

Route::middleware('auth')->group(function () {
    Route::get('/cuenta', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/cuenta', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/cuenta/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::middleware('can:cart.use')->group(function () {
        Route::get('/carrito', CartPage::class)->name('cart.index');
    });
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/warehouse.php';
