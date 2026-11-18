@php($colors = \App\Support\SectionColors::for($product->category->slug))
<x-layouts.app :title="$product->name">
    <a href="{{ route('catalog.index', ['categoria' => $product->category->slug]) }}"
       class="inline-flex min-h-11 items-center gap-1.5 text-[15px] font-bold {{ $colors['ink'] }} hover:underline">
        <span aria-hidden="true">←</span> {{ $product->category->name }}
    </a>

    <div class="mt-2 grid gap-8 md:grid-cols-2">
        <div class="aspect-square overflow-hidden rounded-shelf {{ $colors['tint'] }}">
            @if ($product->image_url)
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
            @else
                <div class="grid h-full w-full place-items-center text-9xl font-extralight tracking-tighter {{ $colors['ink'] }}" aria-hidden="true">{{ mb_substr($product->name, 0, 1) }}</div>
            @endif
        </div>

        <div class="space-y-5">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">{{ $product->name }}</h1>
                <x-availability :product="$product" detailed class="mt-2 text-sm" />
            </div>
            <p class="flex items-baseline gap-1.5">
                <span class="text-4xl font-extrabold tracking-tight">{{ \Illuminate\Support\Number::currency((float) $product->price, 'EUR', 'es') }}</span>
                <span class="text-base text-ink-muted">/ {{ $product->unit_type->suffix() }}</span>
            </p>

            @if ($product->unit_type === \App\Enums\UnitType::Weight)
                <p class="rounded-control border border-stock-low/25 bg-[#FBF3E8] px-4 py-3 text-[15px] text-stock-low">
                    Producto por peso: el importe es aproximado y se ajusta al pesarlo en tienda.
                </p>
            @endif

            @if ($product->description)
                <p class="text-base leading-relaxed text-ink-soft">{{ $product->description }}</p>
            @endif

            <dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2 rounded-card border border-line bg-card p-4 text-[15px]">
                <dt class="font-semibold text-ink-muted">Referencia</dt><dd class="font-mono">{{ $product->sku }}</dd>
                <dt class="font-semibold text-ink-muted">Venta</dt><dd>{{ $product->unit_type->label() }}</dd>
                @if ($product->is_perishable)
                    <dt class="font-semibold text-ink-muted">Conservación</dt><dd>Producto perecedero</dd>
                @endif
            </dl>

            <div class="max-w-sm rounded-card bg-card p-4 shadow-card">
                <livewire:add-to-cart :product="$product" />
            </div>
        </div>
    </div>
</x-layouts.app>
