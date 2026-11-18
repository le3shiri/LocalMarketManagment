@props(['category', 'products'])

@php($colors = \App\Support\SectionColors::for($category->slug))
@php($count = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $products->total() : $products->count())

{{-- Shelf: one section of the shop on its own tint. On phones the row scrolls sideways (.shelf-row). --}}
<section {{ $attributes->class(['mt-7 rounded-shelf p-4 sm:p-6', $colors['tint']]) }} aria-labelledby="estante-{{ $category->slug }}">
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-2 px-1 pb-5">
        <div>
            <h2 id="estante-{{ $category->slug }}" class="text-[28px] font-extrabold leading-none tracking-[-0.04em] sm:text-4xl {{ $colors['ink'] }}">{{ $category->name }}</h2>
            @if ($category->description)
                <p class="mt-1.5 text-[15px] text-ink-soft">{{ $category->description }}</p>
            @endif
        </div>
        <p class="text-sm font-semibold text-ink-soft">{{ $count }} {{ $count === 1 ? 'producto' : 'productos' }}</p>
    </div>
    <div class="shelf-row grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($products as $product)
            <x-product-card :product="$product" wire:key="product-{{ $product->id }}" />
        @endforeach
    </div>
</section>
