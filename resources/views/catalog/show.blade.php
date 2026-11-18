<x-layouts.app :title="$product->name">
    <a href="{{ route('catalog.index', ['categoria' => $product->category->slug]) }}" class="text-sm text-stone-500 hover:underline">← {{ $product->category->name }}</a>

    <div class="mt-4 grid gap-8 md:grid-cols-2">
        <div class="aspect-square overflow-hidden rounded-2xl bg-stone-100">
            @if ($product->image_url)
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
            @else
                <div class="grid h-full w-full place-items-center text-7xl font-semibold text-stone-300">{{ mb_substr($product->name, 0, 1) }}</div>
            @endif
        </div>

        <div class="space-y-4">
            <h1 class="text-3xl font-bold text-stone-900">{{ $product->name }}</h1>
            <p class="text-2xl font-semibold text-emerald-700">{{ $product->formatted_price }}</p>
            <x-availability :product="$product" class="text-sm" />

            @if ($product->unit_type === \App\Enums\UnitType::Weight)
                <p class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    Producto por peso: el importe es aproximado y se ajusta al pesarlo en tienda.
                </p>
            @endif

            @if ($product->description)
                <p class="text-stone-600">{{ $product->description }}</p>
            @endif

            <dl class="grid grid-cols-2 gap-2 text-sm">
                <dt class="text-stone-400">Referencia</dt><dd class="font-mono">{{ $product->sku }}</dd>
                <dt class="text-stone-400">Venta</dt><dd>{{ $product->unit_type->label() }}</dd>
                @if ($product->is_perishable)
                    <dt class="text-stone-400">Conservación</dt><dd>Producto perecedero</dd>
                @endif
            </dl>

            {{ $actions ?? '' }}
        </div>
    </div>
</x-layouts.app>
