<x-layouts.app title="Usuarios">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-[28px] font-extrabold tracking-tight text-ink">Usuarios</h1>
            <p class="text-sm text-ink-muted">{{ $users->total() }} cuentas registradas</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-control bg-action px-5 text-[15px] font-bold text-on-action hover:bg-action-hover">Nuevo usuario</a>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre, email o teléfono"
               class="w-72 min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
        <select name="role" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
            <option value="">Todos los roles</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" @selected(request('role') == $role->id)>{{ $role->label }}</option>
            @endforeach
        </select>
        <x-button variant="secondary">Filtrar</x-button>
    </form>

    <div class="overflow-x-auto rounded-card border border-line bg-white">
        <table class="min-w-full divide-y divide-line text-sm">
            <thead class="bg-sunken text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted">
                <tr>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Rol</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($users as $user)
                    <tr @class(['opacity-60' => ! $user->is_active])>
                        <td class="px-4 py-3 font-medium text-ink">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-ink-soft">{{ $user->email }}</td>
                        <td class="px-4 py-3">{{ $user->role?->label ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-action-soft text-action' => $user->is_active,
                                'bg-sunken text-ink-muted' => ! $user->is_active,
                            ])>{{ $user->is_active ? 'Activa' : 'Desactivada' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.users.edit', $user) }}" class="rounded-control px-2 py-1 text-action hover:bg-action-soft">Editar</a>
                                @unless ($user->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-control px-2 py-1 text-ink-soft hover:bg-sunken">{{ $user->is_active ? 'Desactivar' : 'Activar' }}</button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-ink-muted">No hay usuarios que coincidan con la búsqueda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.app>
