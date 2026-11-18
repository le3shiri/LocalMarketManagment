<x-layouts.app title="Iniciar sesión">
    <div class="mx-auto max-w-md">
        <x-card title="Iniciar sesión">
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <x-input name="email" label="Email" type="email" autocomplete="username" required autofocus />
                <x-input name="password" label="Contraseña" type="password" autocomplete="current-password" required />

                <label class="flex items-center gap-2 text-sm text-stone-600">
                    <input type="checkbox" name="remember" class="rounded border-stone-300 text-emerald-600">
                    Recordarme
                </label>

                <x-button class="w-full">Entrar</x-button>
            </form>

            @if (Route::has('register'))
                <p class="mt-4 text-center text-sm text-stone-500">
                    ¿Aún no tienes cuenta?
                    <a href="{{ route('register') }}" class="font-medium text-emerald-700 hover:underline">Regístrate</a>
                </p>
            @endif
        </x-card>
    </div>
</x-layouts.app>
