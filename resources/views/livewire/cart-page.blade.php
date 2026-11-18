@use('Illuminate\Support\Number')
<div>
    <h1 class="mb-6 text-2xl font-bold text-stone-900">Tu carrito</h1>

    @if ($items->isEmpty())
        <div class="rounded-xl border border-dashed border-stone-300 p-10 text-center">
            <p class="text-stone-500">Tu carrito está vacío.</p>
            <a href="{{ route('catalog.index') }}" class="mt-3 inline-block text-sm font-medium text-emerald-700 hover:underline">Ir al catálogo</a>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            <ul class="divide-y divide-stone-100 rounded-xl border border-stone-200 bg-white lg:col-span-2">
                @foreach ($items as $item)
                    <li class="flex items-center gap-4 p-4" wire:key="line-{{ $item->id }}">
                        <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-lg bg-stone-100 text-stone-300">
                            @if ($item->product->image_url)
                                <img src="{{ $item->product->image_url }}" alt="" class="h-full w-full object-cover">
                            @else
                                {{ mb_substr($item->product->name, 0, 1) }}
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('catalog.show', $item->product) }}" class="font-medium text-stone-900 hover:text-emerald-700">{{ $item->product->name }}</a>
                            <p class="text-xs text-stone-500">{{ $item->product->formatted_price }}</p>
                        </div>
                        <p class="w-20 text-right text-sm">{{ $item->product->formatQuantity($item->quantity) }}</p>
                        <p class="w-24 text-right font-medium">{{ Number::currency($item->line_total, 'EUR', 'es') }}</p>
                    </li>
                @endforeach
            </ul>

            <x-card title="Resumen">
                <div class="flex justify-between text-sm">
                    <span>Subtotal</span>
                    <span class="font-semibold">{{ Number::currency($subtotal, 'EUR', 'es') }}</span>
                </div>
            </x-card>
        </div>
    @endif
</div>
