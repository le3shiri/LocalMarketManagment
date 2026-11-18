<?php

use App\Livewire\Pos\Terminal;
use Illuminate\Support\Facades\Route;

/*
| Counter sales (TPV / POS).
*/
Route::middleware(['auth', 'can:pos.use'])->prefix('caja')->name('pos.')->group(function () {
    Route::get('/', Terminal::class)->name('terminal');
});
