<x-layouts.app title="Inicio">
    <section class="grid gap-8 rounded-shelf border border-line bg-card p-8 sm:p-12 lg:grid-cols-[1.2fr_1fr] lg:items-center">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.08em] text-ink-muted">Tu tienda de barrio, ahora online</p>
            <h1 class="mt-3 text-5xl font-extrabold leading-[0.95] tracking-[-0.04em] text-ink sm:text-6xl">LocalMarket</h1>
            <p class="mt-5 max-w-xl text-lg leading-relaxed text-ink-soft">
                Consulta el catálogo, comprueba la disponibilidad en tiempo real y prepara tu pedido
                para recogerlo en tienda o recibirlo en casa.
            </p>
            @if (Route::has('catalog.index'))
                <a href="{{ route('catalog.index') }}" class="mt-8 inline-flex h-12 items-center rounded-control bg-action px-6 text-[15px] font-bold text-on-action hover:bg-action-hover">
                    Ver catálogo
                </a>
            @endif
        </div>

        {{-- The shop's sections as shelves: the same colours as the catalog. --}}
        @if (Route::has('catalog.index'))
            <nav aria-label="Secciones de la tienda" class="grid gap-3">
                @foreach (\App\Models\Category::ordered()->whereHas('products', fn ($q) => $q->active())->get() as $category)
                    @php($c = \App\Support\SectionColors::for($category->slug))
                    <a href="{{ route('catalog.index', ['categoria' => $category->slug]) }}"
                       class="flex min-h-14 items-center justify-between rounded-card px-5 text-lg font-extrabold tracking-tight hover:brightness-95 {{ $c['tint'] }} {{ $c['ink'] }}">
                        {{ $category->name }}
                        <span aria-hidden="true">→</span>
                    </a>
                @endforeach
            </nav>
        @endif
    </section>
</x-layouts.app>
