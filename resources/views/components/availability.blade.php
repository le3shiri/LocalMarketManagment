@props(['product', 'detailed' => false])

@php($availability = $product->availability())

<p {{ $attributes->class('flex items-center gap-1.5 text-xs') }} data-availability="{{ $availability->value }}">
    <span @class([
        'h-2 w-2 rounded-full',
        'bg-emerald-500' => $availability === \App\Enums\Availability::Available,
        'bg-amber-500' => $availability === \App\Enums\Availability::Low,
        'bg-stone-400' => $availability === \App\Enums\Availability::SoldOut,
    ])></span>
    <span @class([
        'text-emerald-700' => $availability === \App\Enums\Availability::Available,
        'text-amber-700' => $availability === \App\Enums\Availability::Low,
        'text-stone-500' => $availability === \App\Enums\Availability::SoldOut,
    ])>
        {{ $availability->label() }}
        @if ($detailed && $availability === \App\Enums\Availability::Low)
            (quedan {{ $product->formatQuantity($product->stock->available_online) }})
        @endif
    </span>
</p>
