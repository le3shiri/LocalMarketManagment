<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/cuenta', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/cuenta', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/cuenta/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

require __DIR__.'/auth.php';
