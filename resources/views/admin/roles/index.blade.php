<x-layouts.app title="Roles y permisos">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-stone-900">Roles y permisos</h1>
        <p class="text-sm text-stone-500">Cada rol solo puede hacer lo que necesita para su trabajo (principio de mínimo privilegio).</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        @foreach ($roles as $role)
            <x-card>
                <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-stone-900">{{ $role->label }}</h2>
                            <p class="text-sm text-stone-500">{{ $role->description }}</p>
                        </div>
                        <span class="whitespace-nowrap rounded-full bg-stone-100 px-2 py-1 text-xs text-stone-600">{{ $role->users_count }} usuarios</span>
                    </div>

                    @foreach ($permissions as $group => $items)
                        <fieldset class="mb-3">
                            <legend class="mb-1 text-xs font-semibold uppercase tracking-wide text-stone-400">{{ $group }}</legend>
                            @foreach ($items as $permission)
                                @php($locked = $role->isAdmin() && $permission->name === 'roles.manage')
                                <label class="flex items-center gap-2 py-0.5 text-sm">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                           class="rounded border-stone-300 text-emerald-600"
                                           @checked($role->permissions->contains($permission)) @disabled($locked)>
                                    {{ $permission->label }}
                                    <code class="text-xs text-stone-400">{{ $permission->name }}</code>
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
