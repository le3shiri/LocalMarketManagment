<?php

namespace App\Support;

use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Main menu, grouped by area. Each user only sees the entries their permissions allow; a group
 * left with a single entry is shown as a plain link, so cashiers and warehouse staff keep a
 * short flat menu while the owner gets tidy dropdowns.
 */
class Navigation
{
    /**
     * @return list<array{label: string, active: bool, items: list<array{label: string, url: string, active: bool, badge: ?int}>}>
     */
    public static function for(User $user): array
    {
        $groups = [
            ['Panel', [
                ['Panel de control', 'admin.dashboard', 'dashboard.view', ['admin.dashboard']],
            ]],
            ['Tienda', [
                ['Carrito', 'cart.index', 'cart.use', ['cart.*', 'checkout.*'], fn () => $user->cartItems()->count()],
                ['Mis pedidos', 'orders.index', 'cart.use', ['orders.*']],
            ]],
            ['Ventas', [
                ['Caja', 'pos.terminal', 'pos.use', ['pos.*']],
                ['Ventas y pagos', 'admin.sales.index', 'sales.view', ['admin.sales.*']],
                ['Informes', 'admin.reports.index', 'reports.view', ['admin.reports.index', 'admin.reports.export']],
                ['Más vendidos', 'admin.reports.top', 'reports.view', ['admin.reports.top*']],
            ]],
            ['Pedidos', [
                ['Pedidos', 'staff.orders.index', 'orders.manage', ['staff.orders.*']],
                ['Supervisión', 'admin.orders.index', 'orders.supervise', ['admin.orders.*']],
            ]],
            ['Almacén', [
                ['Almacén', 'warehouse.stock.index', 'stock.view', ['warehouse.*'], fn () => ProductStock::belowMinimum()->count()],
            ]],
            ['Catálogo', [
                ['Productos', 'admin.products.index', 'products.manage', ['admin.products.*']],
                ['Categorías', 'admin.categories.index', 'categories.manage', ['admin.categories.*']],
            ]],
            ['Administración', [
                ['Usuarios', 'admin.users.index', 'users.manage', ['admin.users.*']],
                ['Roles y permisos', 'admin.roles.index', 'roles.manage', ['admin.roles.*']],
                ['Historial', 'admin.audit.index', 'audit.view', ['admin.audit.*']],
                ['Copias de seguridad', 'admin.backups.index', 'backups.manage', ['admin.backups.*']],
            ]],
        ];

        $menu = [];

        foreach ($groups as [$label, $entries]) {
            $items = [];

            foreach ($entries as $entry) {
                [$itemLabel, $route, $permission, $patterns] = $entry;

                if (! Route::has($route) || ! $user->can($permission)) {
                    continue;
                }

                $badge = isset($entry[4]) ? ($entry[4])() : null;

                $items[] = [
                    'label' => $itemLabel,
                    'url' => route($route),
                    'active' => request()->routeIs(...$patterns),
                    'badge' => $badge ?: null,
                ];
            }

            if ($items !== []) {
                $menu[] = [
                    'label' => count($items) === 1 ? $items[0]['label'] : $label,
                    'active' => collect($items)->contains('active', true),
                    'items' => $items,
                ];
            }
        }

        return $menu;
    }
}
