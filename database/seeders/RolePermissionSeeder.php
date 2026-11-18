<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Roles and permissions from section 4 of the project proposal.
 * Idempotent: safe to run on every deploy.
 */
class RolePermissionSeeder extends Seeder
{
    /** name => [label, group] */
    public const PERMISSIONS = [
        'users.manage' => ['Gestionar usuarios', 'Usuarios y roles'],
        'roles.manage' => ['Gestionar roles y permisos', 'Usuarios y roles'],
        'audit.view' => ['Consultar el historial de operaciones', 'Usuarios y roles'],
        'categories.manage' => ['Gestionar categorías', 'Catálogo'],
        'products.manage' => ['Crear y editar productos', 'Catálogo'],
        'stock.view' => ['Consultar el inventario', 'Almacén'],
        'stock.entry' => ['Registrar entradas de mercancía', 'Almacén'],
        'cart.use' => ['Comprar en la tienda online', 'Tienda online'],
        'orders.manage' => ['Gestionar pedidos (preparar, entregar, cancelar)', 'Pedidos'],
        'orders.supervise' => ['Supervisar todos los pedidos e importes por canal', 'Pedidos'],
        'pos.use' => ['Usar el terminal de caja (TPV)', 'Caja'],
        'sales.view' => ['Consultar ventas y pagos', 'Caja'],
        'refunds.manage' => ['Registrar devoluciones y reembolsos', 'Caja'],
    ];

    /** role => [label, description, permissions] */
    public const ROLES = [
        Role::ADMIN => ['Administrador general', 'Configuración y administración global del sistema.', ['*']],
        Role::CASHIER => ['Usuario de caja', 'Ventas en tienda física y preparación de pedidos.', ['stock.view', 'orders.manage', 'pos.use']],
        Role::WAREHOUSE => ['Encargado de almacén', 'Control de existencias, entradas y ubicaciones.', ['stock.view', 'stock.entry']],
        Role::CUSTOMER => ['Cliente', 'Compra en la tienda online y consulta sus pedidos.', ['cart.use']],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name => [$label, $group]) {
            Permission::updateOrCreate(['name' => $name], compact('label', 'group'));
        }

        foreach (self::ROLES as $name => [$label, $description, $permissions]) {
            $role = Role::updateOrCreate(['name' => $name], compact('label', 'description'));

            $ids = $permissions === ['*']
                ? Permission::pluck('id')
                : Permission::whereIn('name', $permissions)->pluck('id');

            // Only add defaults; never strip permissions an admin granted from the panel.
            $role->permissions()->syncWithoutDetaching($ids);
        }
    }
}
