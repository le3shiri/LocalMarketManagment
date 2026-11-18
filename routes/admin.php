<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderSupervisionController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SalesReportController;
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
        Route::patch('products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');
    });

    Route::get('pedidos', [OrderSupervisionController::class, 'index'])->middleware('can:orders.supervise')->name('orders.index');

    Route::get('/', DashboardController::class)->middleware('can:dashboard.view')->name('dashboard');

    Route::middleware('can:backups.manage')->group(function () {
        Route::get('copias', [BackupController::class, 'index'])->name('backups.index');
        Route::post('copias', [BackupController::class, 'store'])->name('backups.store');
        Route::get('copias/{name}', [BackupController::class, 'download'])->where('name', '[A-Za-z0-9.\-]+')->name('backups.download');
    });

    Route::middleware('can:reports.view')->group(function () {
        Route::get('informes', [ReportController::class, 'index'])->name('reports.index');
        Route::get('informes/exportar', [ReportController::class, 'export'])->name('reports.export');
    });

    Route::get('ventas',[SalesReportController::class, 'index'])->middleware('can:sales.view')->name('sales.index');

    Route::get('historial', [AuditLogController::class, 'index'])->middleware('can:audit.view')->name('audit.index');

    Route::middleware('can:roles.manage')->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });
});
