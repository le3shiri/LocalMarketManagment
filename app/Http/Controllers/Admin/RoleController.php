<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::with('permissions')->withCount('users')->orderBy('id')->get(),
            'permissions' => Permission::orderBy('group')->orderBy('id')->get()->groupBy('group'),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $ids = collect($data['permissions'] ?? []);

        // The admin role must always be able to manage roles, otherwise nobody could undo the change.
        if ($role->isAdmin()) {
            $ids->push(Permission::where('name', 'roles.manage')->value('id'));
        }

        $result = $role->permissions()->sync($ids->unique());

        if ($result['attached'] || $result['detached']) {
            $names = fn (array $ids) => Permission::whereIn('id', $ids)->pluck('name')->all();
            AuditLog::record('permissions', "Cambio de permisos del rol «{$role->label}»", $role, [
                'añadidos' => $names($result['attached']),
                'retirados' => $names($result['detached']),
            ]);
        }

        return back()->with('status', "Permisos del rol «{$role->label}» actualizados.");
    }
}
