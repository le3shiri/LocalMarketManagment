<x-layouts.app title="Usuarios">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-stone-900">Usuarios</h1>
            <p class="text-sm text-stone-500">{{ $users->total() }} cuentas registradas</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Nuevo usuario</a>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre, email o teléfono"
               class="w-72 rounded-lg border border-stone-300 px-3 py-2 text-sm">
        <select name="role" class="rounded-lg border border-stone-300 px-3 py-2 text-sm">
            <option value="">Todos los roles</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" @selected(request('role') == $role->id)>{{ $role->label }}</option>
            @endforeach
        </select>
        <x-button variant="secondary">Filtrar</x-button>
    </form>

    <div class="overflow-x-auto rounded-xl border border-stone-200 bg-white">
        <table class="min-w-full divide-y divide-stone-200 text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                <tr>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Rol</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($users as $user)
                    <tr @class(['opacity-60' => ! $user->is_active])>
                        <td class="px-4 py-3 font-medium text-stone-900">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-stone-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">{{ $user->role?->label ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-emerald-50 text-emerald-700' => $user->is_active,
                                'bg-stone-100 text-stone-500' => ! $user->is_active,
                            ])>{{ $user->is_active ? 'Activa' : 'Desactivada' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.users.edit', $user) }}" class="rounded-md px-2 py-1 text-emerald-700 hover:bg-emerald-50">Editar</a>
                                @unless ($user->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-md px-2 py-1 text-stone-600 hover:bg-stone-100">{{ $user->is_active ? 'Desactivar' : 'Activar' }}</button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-stone-500">No hay usuarios que coincidan con la búsqueda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.app>
