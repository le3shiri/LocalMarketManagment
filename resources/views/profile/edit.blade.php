<x-layouts.app title="Mi cuenta">
    <h1 class="mb-6 text-[28px] font-extrabold tracking-tight text-ink">Mi cuenta</h1>

    <div class="grid gap-6 md:grid-cols-2">
        <x-card title="Datos personales">
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')
                <x-input name="name" label="Nombre y apellidos" :value="$user->name" required />
                <x-input name="email" label="Email" type="email" :value="$user->email" required />
                <x-input name="phone" label="Teléfono" type="tel" :value="$user->phone" />
                <x-input name="address" label="Dirección" :value="$user->address" />
                <x-button>Guardar cambios</x-button>
            </form>
        </x-card>

        <x-card title="Cambiar contraseña">
            <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                @csrf
                @method('PUT')
                @foreach (['current_password' => 'Contraseña actual', 'password' => 'Nueva contraseña', 'password_confirmation' => 'Repite la nueva contraseña'] as $field => $label)
                    <div class="space-y-1">
                        <label for="{{ $field }}" class="block text-sm font-medium text-ink-soft">{{ $label }}</label>
                        <input id="{{ $field }}" name="{{ $field }}" type="password" required
                               class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px] shadow-card focus:outline-none focus:ring-2 focus:ring-action-ring">
                        @error($field, 'password')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
                <x-button variant="secondary">Actualizar contraseña</x-button>
            </form>
        </x-card>
    </div>
</x-layouts.app>
