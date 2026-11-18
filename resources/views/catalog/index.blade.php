<x-layouts.app title="Catálogo">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-stone-900">{{ $current?->name ?? 'Catálogo' }}</h1>
        <p class="text-sm text-stone-500">{{ $current?->description ?? 'Productos frescos y de despensa de tu tienda de barrio.' }}</p>
    </div>

    <div class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route('catalog.index') }}" @class([
            'rounded-full border px-3 py-1 text-sm',
            'border-emerald-600 bg-emerald-600 text-white' => ! $current,
            'border-stone-300 bg-white text-stone-600 hover:border-emerald-600' => $current,
        ])>Todo</a>
        @foreach ($categories as $category)
            <a href="{{ route('catalog.index', ['categoria' => $category->slug]) }}" @class([
                'rounded-full border px-3 py-1 text-sm',
                'border-emerald-600 bg-emerald-600 text-white' => $current?->is($category),
                'border-stone-300 bg-white text-stone-600 hover:border-emerald-600' => ! $current?->is($category),
            ])>{{ $category->name }}</a>
        @endforeach
    </div>

    @if ($products->isEmpty())
        <p class="rounded-xl border border-dashed border-stone-300 p-10 text-center text-stone-500">No hay productos en esta categoría todavía.</p>
    @else
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
        <div class="mt-6">{{ $products->links() }}</div>
    @endif
</x-layouts.app>
