<x-layouts.app title="Inicio">
    <section class="rounded-2xl bg-gradient-to-br from-emerald-600 to-emerald-800 px-8 py-14 text-white">
        <p class="text-sm uppercase tracking-widest text-emerald-100">Tu tienda de barrio, ahora online</p>
        <h1 class="mt-2 text-4xl font-bold">LocalMarket</h1>
        <p class="mt-4 max-w-xl text-emerald-50">
            Consulta el catálogo, comprueba la disponibilidad en tiempo real y prepara tu pedido
            para recogerlo en tienda o recibirlo en casa.
        </p>
        @if (Route::has('catalog.index'))
            <a href="{{ route('catalog.index') }}" class="mt-8 inline-block rounded-lg bg-white px-5 py-3 font-medium text-emerald-700 hover:bg-emerald-50">
                Ver catálogo
            </a>
        @endif
    </section>
</x-layouts.app>
