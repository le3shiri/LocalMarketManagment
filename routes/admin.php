<?php

use App\Http\Controllers\Admin\RoleController;
use Illuminate\Support\Facades\Route;

/*
| Back-office routes. Every group is guarded by the permission it needs, never by role name,
| so an admin can re-shape what each role may do from the Roles screen.
*/
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::middleware('can:roles.manage')->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });
});
