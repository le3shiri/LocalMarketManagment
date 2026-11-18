<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        // Demo accounts for local development only (password: "password").
        $accounts = [
            ['Ana Beltrán', 'admin@localmarket.test', Role::ADMIN],
            ['Laura Caja', 'caja@localmarket.test', Role::CASHIER],
            ['Jordi Almacén', 'almacen@localmarket.test', Role::WAREHOUSE],
            ['Marta Puig', 'cliente@localmarket.test', Role::CUSTOMER],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            User::factory()->withRole($role)->create(compact('name', 'email'));
        }
    }
}
