<x-layouts.app title="Crear cuenta">
    <div class="mx-auto max-w-md">
        <x-card title="Crear cuenta de cliente">
            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf
                <x-input name="name" label="Nombre y apellidos" required autofocus autocomplete="name" />
                <x-input name="email" label="Email" type="email" required autocomplete="username" />
                <x-input name="phone" label="Teléfono (opcional)" type="tel" autocomplete="tel" />
                <x-input name="password" label="Contraseña" type="password" required autocomplete="new-password" />
                <x-input name="password_confirmation" label="Repite la contraseña" type="password" required autocomplete="new-password" />

                <x-button class="w-full">Crear cuenta</x-button>
            </form>

            <p class="mt-4 text-center text-sm text-stone-500">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}" class="font-medium text-emerald-700 hover:underline">Inicia sesión</a>
            </p>
        </x-card>
    </div>
</x-layouts.app>
