@props(['product'])

<article class="flex flex-col overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm transition hover:shadow-md">
    <a href="{{ route('catalog.show', $product) }}" class="block aspect-square bg-stone-100">
        @if ($product->image_url)
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover" loading="lazy">
        @else
            <div class="grid h-full w-full place-items-center text-3xl font-semibold text-stone-300">{{ mb_substr($product->name, 0, 1) }}</div>
        @endif
    </a>
    <div class="flex flex-1 flex-col gap-1 p-4">
        <p class="text-xs uppercase tracking-wide text-stone-400">{{ $product->category->name }}</p>
        <a href="{{ route('catalog.show', $product) }}" class="font-medium text-stone-900 hover:text-emerald-700">{{ $product->name }}</a>
        <p class="font-semibold text-emerald-700">{{ $product->formatted_price }}</p>
        <x-availability :product="$product" class="mt-auto pt-2" />
        {{ $slot }}
    </div>
</article>
