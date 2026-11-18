<x-layouts.app title="Crear cuenta">
    <x-auth-shell title="Crear cuenta de cliente" subtitle="Compra online y recoge en la tienda o recíbelo en casa.">
        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <x-input name="name" label="Nombre y apellidos" required autofocus autocomplete="name" />
            <x-input name="email" label="Email" type="email" required autocomplete="username" />
            <x-input name="phone" label="Teléfono (opcional)" type="tel" autocomplete="tel" />
            <x-input name="password" label="Contraseña" type="password" required autocomplete="new-password" />
            <x-input name="password_confirmation" label="Repite la contraseña" type="password" required autocomplete="new-password" />

            <x-button class="h-12 w-full">Crear cuenta</x-button>
        </form>

        <p class="mt-5 text-center text-[15px] text-ink-muted">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="font-bold text-action hover:underline">Inicia sesión</a>
        </p>
    </x-auth-shell>
</x-layouts.app>
