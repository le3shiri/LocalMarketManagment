@use('Illuminate\Support\Number')
<div>
    <div class="mb-6 flex items-end justify-between gap-4">
        <h1 class="text-2xl font-bold text-stone-900">Tu carrito</h1>
        @if ($items->isNotEmpty())
            <button type="button" wire:click="clear" wire:confirm="¿Vaciar el carrito?" class="text-sm text-stone-500 hover:text-red-600">Vaciar carrito</button>
        @endif
    </div>

    @if ($error)
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $error }}</div>
    @endif

    @if ($items->isEmpty())
        <div class="rounded-xl border border-dashed border-stone-300 p-10 text-center">
            <p class="text-stone-500">Tu carrito está vacío.</p>
            <a href="{{ route('catalog.index') }}" class="mt-3 inline-block text-sm font-medium text-emerald-700 hover:underline">Ir al catálogo</a>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            <ul class="divide-y divide-stone-100 rounded-xl border border-stone-200 bg-white lg:col-span-2" wire:loading.class="opacity-70">
                @foreach ($items as $item)
                    <li class="flex flex-wrap items-center gap-4 p-4" wire:key="line-{{ $item->id }}">
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
                            @isset($shortages[$item->id])
                                <p class="mt-1 text-xs font-medium text-red-600">
                                    @if ($shortages[$item->id] > 0)
                                        Solo quedan {{ $item->product->formatQuantity($shortages[$item->id]) }}: ajusta la cantidad.
                                    @else
                                        Se ha agotado mientras comprabas.
                                    @endif
                                </p>
                            @endisset
                        </div>

                        <div class="flex items-center rounded-lg border border-stone-300">
                            <button type="button" wire:click="decrement({{ $item->id }})" class="px-2.5 py-1 text-stone-600 hover:bg-stone-100" aria-label="Quitar {{ $item->product->unit_type->step() }}">−</button>
                            <input type="number" value="{{ $item->quantity }}" step="{{ $item->product->unit_type->step() }}" min="0"
                                   wire:change="updateQuantity({{ $item->id }}, $event.target.value)"
                                   class="w-16 border-0 py-1 text-center text-sm focus:ring-0" aria-label="Cantidad de {{ $item->product->name }}">
                            <button type="button" wire:click="increment({{ $item->id }})" class="px-2.5 py-1 text-stone-600 hover:bg-stone-100" aria-label="Añadir {{ $item->product->unit_type->step() }}">+</button>
                        </div>
                        <span class="w-6 text-xs text-stone-500">{{ $item->product->unit_type->suffix() }}</span>

                        <p class="w-24 text-right font-medium">{{ Number::currency($item->line_total, 'EUR', 'es') }}</p>
                        <button type="button" wire:click="remove({{ $item->id }})" class="text-stone-400 hover:text-red-600" title="Eliminar">✕</button>
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
