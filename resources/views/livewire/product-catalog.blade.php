<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-stone-900">{{ $this->currentCategory?->name ?? 'Catálogo' }}</h1>
            <p class="text-sm text-stone-500">{{ $this->currentCategory?->description ?? 'Productos frescos y de despensa de tu tienda de barrio.' }}</p>
        </div>
        <div class="relative w-full sm:w-80">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar productos…" aria-label="Buscar productos"
                   class="w-full rounded-lg border border-stone-300 py-2 pl-3 pr-9 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <span wire:loading wire:target="search" class="absolute right-3 top-2.5 h-4 w-4 animate-spin rounded-full border-2 border-emerald-500 border-t-transparent"></span>
        </div>
    </div>

    <div class="mb-6 flex flex-wrap gap-2">
        <button type="button" wire:click="selectCategory('')" @class([
            'rounded-full border px-3 py-1 text-sm',
            'border-emerald-600 bg-emerald-600 text-white' => ! $this->currentCategory,
            'border-stone-300 bg-white text-stone-600 hover:border-emerald-600' => $this->currentCategory,
        ])>Todo</button>
        @foreach ($this->categories as $item)
            <button type="button" wire:click="selectCategory('{{ $item->slug }}')" wire:key="cat-{{ $item->id }}" @class([
                'rounded-full border px-3 py-1 text-sm',
                'border-emerald-600 bg-emerald-600 text-white' => $this->currentCategory?->is($item),
                'border-stone-300 bg-white text-stone-600 hover:border-emerald-600' => ! $this->currentCategory?->is($item),
            ])>{{ $item->name }}</button>
        @endforeach
    </div>

    @if ($products->isEmpty())
        <div class="rounded-xl border border-dashed border-stone-300 p-10 text-center text-stone-500">
            @if (trim($search) !== '')
                <p>No hemos encontrado productos para «{{ $search }}».</p>
            @else
                <p>No hay productos en esta categoría todavía.</p>
            @endif
            <button type="button" wire:click="clearFilters" class="mt-3 text-sm font-medium text-emerald-700 hover:underline">Ver todo el catálogo</button>
        </div>
    @else
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4" wire:loading.class="opacity-60">
            @foreach ($products as $product)
                <x-product-card :product="$product" wire:key="product-{{ $product->id }}" />
            @endforeach
        </div>
        <div class="mt-6">{{ $products->links() }}</div>
    @endif
</div>
