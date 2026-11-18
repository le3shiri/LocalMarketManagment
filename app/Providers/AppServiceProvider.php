<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Every permission stored in the database is a Gate ability, so routes can use
        // `can:products.manage` and views `@can('products.manage')`.
        // Returning null lets explicit policies decide for abilities that aren't permissions.
        Gate::before(fn (User $user, string $ability) => $user->hasPermission($ability) ?: null);

        // Security-relevant authentication events go to the operations history.
        Event::listen(Login::class, fn (Login $event) => AuditLog::record('login', "Inicio de sesión de {$event->user->email}", $event->user, user: $event->user));
        Event::listen(Logout::class, fn (Logout $event) => $event->user && AuditLog::record('logout', "Cierre de sesión de {$event->user->email}", $event->user, user: $event->user));
        Event::listen(Failed::class, fn (Failed $event) => AuditLog::record('login_failed', 'Intento de acceso fallido con '.($event->credentials['email'] ?? '¿?'), $event->user, user: null));
    }
}
