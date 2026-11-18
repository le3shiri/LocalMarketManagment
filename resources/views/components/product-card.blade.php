@props(['product', 'tag' => false])

@php($soldOut = ! $product->availability()->canBuy())
@php($colors = \App\Support\SectionColors::for($product->category->slug))

{{-- ProductCard: name, stock, price and the two-tap add. Sold out uses real colours, never opacity. --}}
<article {{ $attributes->class([
    'flex flex-col rounded-card p-[18px]',
    'border border-line bg-soldout' => $soldOut,
    'bg-card shadow-card' => ! $soldOut,
]) }}>
    @if ($tag)
        <span class="mb-3 self-start rounded-full px-2.5 py-1 text-xs font-extrabold {{ $colors['tint'] }} {{ $colors['ink'] }}">{{ $product->category->name }}</span>
    @endif

    {{-- Image preview: the product photo, or a placeholder in its section's colour until one is uploaded. --}}
    <a href="{{ route('catalog.show', $product) }}" tabindex="-1" aria-hidden="true" @class([
        'relative mb-4 block aspect-[4/3] overflow-hidden rounded-control',
        $colors['tint'] => ! $product->image_url,
        'bg-sunken' => (bool) $product->image_url,
    ])>
        @if ($product->image_url)
            <img src="{{ $product->image_url }}" alt="" loading="lazy" @class([
                'h-full w-full object-cover transition duration-300 hover:scale-[1.03]',
                'grayscale' => $soldOut,
            ])>
        @else
            <span @class(['absolute inset-0 grid place-items-center text-7xl font-extralight tracking-tighter', $colors['ink'], 'opacity-50' => $soldOut]) data-placeholder>{{ mb_substr($product->name, 0, 1) }}</span>
        @endif
        @if ($soldOut)
            <span class="absolute left-2.5 top-2.5 rounded-full bg-card px-2.5 py-1 text-xs font-extrabold text-ink-disabled">Agotado hoy</span>
        @endif
    </a>

    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('catalog.show', $product) }}" @class([
                'block text-[17px] font-bold leading-snug hover:text-action',
                'text-ink-disabled' => $soldOut,
                'text-ink' => ! $soldOut,
            ])>{{ $product->name }}</a>
            <x-availability :product="$product" class="mt-1.5" />
        </div>
        <p class="whitespace-nowrap text-right">
            <span @class(['block text-[22px] font-extrabold leading-tight tracking-tight', 'text-ink-disabled' => $soldOut])>{{ \Illuminate\Support\Number::currency((float) $product->price, 'EUR', 'es') }}</span>
            <span class="block text-[13px] text-ink-muted">/ {{ $product->unit_type->suffix() }}</span>
        </p>
    </div>

    <div class="mt-auto">
        <livewire:add-to-cart :product="$product" :compact="true" :key="'add-to-cart-'.$product->id" />
    </div>
    {{ $slot }}
</article>
