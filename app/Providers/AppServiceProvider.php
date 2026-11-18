<?php

namespace App\Providers;

use App\Models\User;
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
    }
}
