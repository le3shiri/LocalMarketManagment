<div>
    @php($term = trim($search))

    {{-- Sticky bar: heading, search and the section chips stay in reach while scrolling the shelves. --}}
    <div class="sticky top-0 z-10 -mx-4 border-b border-line bg-page/95 px-4 pb-3 pt-2 backdrop-blur sm:-mx-6 sm:px-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-extrabold tracking-tight text-ink">
                @if ($term !== '')
                    {{ $products->total() }} {{ $products->total() === 1 ? 'resultado' : 'resultados' }} para «{{ $term }}»
                @elseif ($this->currentCategory)
                    {{ $this->currentCategory->name }}
                @else
                    Pasa por las estanterías
                @endif
            </h1>
            <div @class([
                'relative flex h-12 w-full items-center gap-2.5 rounded-control px-4 sm:w-96',
                'border-2 border-action bg-card' => $term !== '',
                'bg-sunken' => $term === '',
            ])>
                <svg class="h-[18px] w-[18px] flex-none {{ $term !== '' ? 'text-action' : 'text-ink-muted' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <label for="buscar" class="sr-only">Buscar productos</label>
                <input id="buscar" type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar en la tienda"
                       class="h-full min-w-0 flex-1 border-0 bg-transparent p-0 text-base font-semibold text-ink placeholder:font-normal placeholder:text-ink-muted focus:ring-0">
                <span wire:loading wire:target="search" class="h-4 w-4 flex-none animate-spin rounded-full border-2 border-action border-t-transparent" aria-hidden="true"></span>
            </div>
        </div>

        <nav aria-label="Secciones" class="mt-3 flex gap-2 overflow-x-auto pb-1 sm:flex-wrap sm:overflow-visible">
            <button type="button" wire:click="selectCategory('')" @if (! $this->currentCategory) aria-current="true" @endif @class([
                'inline-flex min-h-11 flex-none items-center rounded-full px-4 text-sm font-bold',
                'bg-ink text-on-action' => ! $this->currentCategory,
                'border border-line bg-card text-ink hover:bg-sunken' => $this->currentCategory,
            ])>Todo</button>
            @foreach ($this->categories as $item)
                @php($c = \App\Support\SectionColors::for($item->slug))
                @php($current = $this->currentCategory?->is($item))
                <button type="button" wire:click="selectCategory('{{ $item->slug }}')" wire:key="cat-{{ $item->id }}" @if ($current) aria-current="true" @endif
                        class="inline-flex min-h-11 flex-none items-center rounded-full px-4 text-sm font-bold {{ $current ? $c['fill'].' text-on-action' : $c['tint'].' '.$c['ink'].' hover:brightness-95' }}">
                    {{ $item->name }}
                </button>
            @endforeach
        </nav>
    </div>

    <div wire:loading.class="opacity-60" wire:target="search, selectCategory, clearFilters" aria-live="polite">
        @if ($shelves)
            {{-- The shop, shelf by shelf: every section shows all its products. --}}
            @foreach ($shelves as $shelf)
                <x-shelf :category="$shelf" :products="$shelf->products" wire:key="shelf-{{ $shelf->id }}" />
            @endforeach
        @elseif ($products->isEmpty())
            <section role="status" class="mx-auto mt-10 max-w-xl rounded-shelf border border-line bg-card px-8 py-12 text-center">
                <div class="mx-auto grid h-16 w-16 place-items-center rounded-[20px] bg-sunken" aria-hidden="true">
                    <svg class="h-7 w-7 text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M8.5 11h5"/></svg>
                </div>
                @if ($term !== '')
                    <h2 class="mt-5 text-2xl font-extrabold tracking-tight">No hemos encontrado productos para «{{ $term }}»</h2>
                    <p class="mt-2 text-base text-ink-soft">Prueba con otra palabra o recorre las secciones de la tienda.</p>
                @else
                    <h2 class="mt-5 text-2xl font-extrabold tracking-tight">No hay productos en esta sección todavía</h2>
                    <p class="mt-2 text-base text-ink-soft">Vuelve pronto o recorre las demás secciones.</p>
                @endif
                <button type="button" wire:click="clearFilters" class="mt-6 inline-flex h-12 items-center rounded-control bg-action px-6 text-[15px] font-bold text-on-action hover:bg-action-hover">Ver todo el catálogo</button>
            </section>
        @elseif ($this->currentCategory && $term === '')
            <x-shelf :category="$this->currentCategory" :products="$products" />
            <div class="mt-6">{{ $products->links() }}</div>
        @else
            {{-- Search results: one plain grid; the coloured tag says which shelf each product comes from. --}}
            <p class="mt-5 text-[15px] text-ink-soft">
                @if ($this->currentCategory)
                    En {{ $this->currentCategory->name }}.
                @endif
                <button type="button" wire:click="clearFilters" class="font-bold text-action underline-offset-2 hover:underline">Volver a las estanterías</button>
            </p>
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :product="$product" :tag="true" wire:key="product-{{ $product->id }}" />
                @endforeach
            </div>
            <div class="mt-6">{{ $products->links() }}</div>
        @endif
    </div>
</div>
