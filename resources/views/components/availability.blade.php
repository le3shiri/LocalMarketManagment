@props(['product', 'detailed' => false])

@php($availability = $product->availability())
@php($tone = match ($availability) {
    \App\Enums\Availability::Available => ['text' => 'text-stock-ok', 'dot' => 'bg-stock-ok'],
    \App\Enums\Availability::Low => ['text' => 'text-stock-low', 'dot' => 'bg-stock-low'],
    \App\Enums\Availability::SoldOut => ['text' => 'text-stock-out', 'dot' => 'bg-stock-out'],
})

{{-- StockStatus: always a dot plus a word, never colour alone. --}}
<p {{ $attributes->class(['flex items-center gap-1.5 text-[13px] font-bold', $tone['text']]) }} data-availability="{{ $availability->value }}">
    <span class="h-[7px] w-[7px] flex-none rounded-full {{ $tone['dot'] }}" aria-hidden="true"></span>
    <span>
        {{ $availability->label() }}
        @if ($detailed && $availability === \App\Enums\Availability::Low)
            (quedan {{ $product->formatQuantity($product->stock->available_online) }})
        @endif
    </span>
</p>
