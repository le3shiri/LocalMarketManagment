<x-layouts.app :title="$user->exists ? 'Editar usuario' : 'Nuevo usuario'">
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-ink-muted hover:underline">← Volver a usuarios</a>

        <x-card :title="$user->exists ? 'Editar '.$user->name : 'Nuevo usuario'" class="mt-3">
            <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="grid gap-4 sm:grid-cols-2">
                @csrf
                @if ($user->exists) @method('PUT') @endif

                <x-input name="name" label="Nombre" :value="$user->name" required />
                <x-input name="email" label="Email" type="email" :value="$user->email" required />
                <x-input name="phone" label="Teléfono" type="tel" :value="$user->phone" />
                <x-input name="address" label="Dirección" :value="$user->address" />

                <div class="space-y-1">
                    <label for="role_id" class="block text-sm font-medium text-ink-soft">Rol</label>
                    <select id="role_id" name="role_id" class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ $role->label }}</option>
                        @endforeach
                    </select>
                    @error('role_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-end pb-2">
                    <input type="hidden" name="is_active" value="0">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" class="h-5 w-5 rounded border-line-strong accent-action" @checked(old('is_active', $user->is_active))>
                        Cuenta activa
                    </label>
                    @error('is_active') <p class="ml-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <x-input name="password" :label="$user->exists ? 'Nueva contraseña (opcional)' : 'Contraseña'" type="password" autocomplete="new-password" />
                <x-input name="password_confirmation" label="Repite la contraseña" type="password" autocomplete="new-password" />

                <div class="sm:col-span-2">
                    <x-button>{{ $user->exists ? 'Guardar cambios' : 'Crear usuario' }}</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
