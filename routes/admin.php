<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
| Back-office routes. Every group is guarded by the permission it needs, never by role name,
| so an admin can re-shape what each role may do from the Roles screen.
*/
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::middleware('can:users.manage')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::patch('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
    });

    Route::middleware('can:categories.manage')->group(function () {
        Route::resource('categories', CategoryController::class)->except(['show', 'create']);
    });

    Route::middleware('can:products.manage')->group(function () {
        Route::resource('products', ProductController::class)->except(['show', 'destroy']);
    });

    Route::middleware('can:roles.manage')->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });
});
