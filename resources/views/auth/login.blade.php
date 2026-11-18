<x-layouts.app title="Iniciar sesión">
    <x-auth-shell title="Iniciar sesión" subtitle="Entra para comprar online, seguir tus pedidos o trabajar en la tienda.">
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <x-input name="email" label="Email" type="email" autocomplete="username" required autofocus />
            <x-input name="password" label="Contraseña" type="password" autocomplete="current-password" required />

            <label class="inline-flex min-h-11 cursor-pointer items-center gap-2.5 text-[15px] font-semibold text-ink-soft">
                <input type="checkbox" name="remember" class="h-5 w-5 rounded border-line-strong accent-action">
                Recordarme
            </label>

            <x-button class="h-12 w-full">Entrar</x-button>
        </form>

        @if (Route::has('register'))
            <p class="mt-5 text-center text-[15px] text-ink-muted">
                ¿Aún no tienes cuenta?
                <a href="{{ route('register') }}" class="font-bold text-action hover:underline">Regístrate</a>
            </p>
        @endif
    </x-auth-shell>
</x-layouts.app>
