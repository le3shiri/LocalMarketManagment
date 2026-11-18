<x-layouts.app title="Roles y permisos">
    <div class="mb-6">
        <h1 class="text-[28px] font-extrabold tracking-tight text-ink">Roles y permisos</h1>
        <p class="text-sm text-ink-muted">Cada rol solo puede hacer lo que necesita para su trabajo (principio de mínimo privilegio).</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        @foreach ($roles as $role)
            <x-card>
                <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-ink">{{ $role->label }}</h2>
                            <p class="text-sm text-ink-muted">{{ $role->description }}</p>
                        </div>
                        <span class="whitespace-nowrap rounded-full bg-sunken px-2 py-1 text-xs text-ink-soft">{{ $role->users_count }} usuarios</span>
                    </div>

                    @foreach ($permissions as $group => $items)
                        <fieldset class="mb-3">
                            <legend class="mb-1 text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ $group }}</legend>
                            @foreach ($items as $permission)
                                @php($locked = $role->isAdmin() && $permission->name === 'roles.manage')
                                <label class="flex items-center gap-2 py-0.5 text-sm">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                           class="h-5 w-5 rounded border-line-strong accent-action"
                                           @checked($role->permissions->contains($permission)) @disabled($locked)>
                                    {{ $permission->label }}
                                    <code class="text-xs text-ink-muted">{{ $permission->name }}</code>
                                </label>
                            @endforeach
                        </fieldset>
                    @endforeach

                    <x-button variant="secondary" class="mt-2">Guardar permisos</x-button>
                </form>
            </x-card>
        @endforeach
    </div>
</x-layouts.app>
