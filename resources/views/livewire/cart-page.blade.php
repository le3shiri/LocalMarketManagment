@use('Illuminate\Support\Number')
<div>
    <div class="mb-6 flex items-end justify-between gap-4">
        <h1 class="text-[28px] font-extrabold tracking-tight text-ink">Tu carrito</h1>
        @if ($items->isNotEmpty())
            <button type="button" wire:click="clear" wire:confirm="¿Vaciar el carrito?" class="text-sm text-ink-muted hover:text-red-600">Vaciar carrito</button>
        @endif
    </div>

    @if ($repeat = session('repeat'))
        @if ($repeat['adjusted'] || $repeat['skipped'])
            <div class="mb-4 rounded-control border border-stock-low/25 bg-warn-soft px-4 py-3 text-sm text-stock-low" role="status" data-repeat-report>
                @if ($repeat['adjusted'])
                    <p class="font-medium">Cantidades ajustadas a la disponibilidad:</p>
                    <ul class="mb-2 list-inside list-disc">@foreach ($repeat['adjusted'] as $line)<li>{{ $line }}</li>@endforeach</ul>
                @endif
                @if ($repeat['skipped'])
                    <p class="font-medium">No se han podido añadir:</p>
                    <ul class="list-inside list-disc">@foreach ($repeat['skipped'] as $line)<li>{{ $line }}</li>@endforeach</ul>
                @endif
            </div>
        @endif
    @endif

    @if ($error)
        <div class="mb-4 rounded-control border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $error }}</div>
    @endif

    @if ($items->isEmpty())
        <div class="rounded-card border border-dashed border-line-strong p-10 text-center">
            <p class="text-ink-muted">Tu carrito está vacío.</p>
            <a href="{{ route('catalog.index') }}" class="mt-3 inline-block text-sm font-medium text-action hover:underline">Ir al catálogo</a>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            <ul class="divide-y divide-line rounded-card border border-line bg-white lg:col-span-2" wire:loading.class="opacity-70">
                @foreach ($items as $item)
                    <li class="flex flex-wrap items-center gap-4 p-4" wire:key="line-{{ $item->id }}">
                        <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-control bg-sunken text-line-dashed">
                            @if ($item->product->image_url)
                                <img src="{{ $item->product->image_url }}" alt="" class="h-full w-full object-cover">
                            @else
                                {{ mb_substr($item->product->name, 0, 1) }}
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('catalog.show', $item->product) }}" class="font-medium text-ink hover:text-action">{{ $item->product->name }}</a>
                            <p class="text-xs text-ink-muted">{{ $item->product->formatted_price }}</p>
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

                        <div class="flex items-center rounded-control border border-line-strong">
                            <button type="button" wire:click="decrement({{ $item->id }})" class="px-2.5 py-1 text-ink-soft hover:bg-sunken" aria-label="Quitar {{ $item->product->unit_type->step() }}">−</button>
                            <input type="number" value="{{ $item->quantity }}" step="{{ $item->product->unit_type->step() }}" min="0"
                                   wire:change="updateQuantity({{ $item->id }}, $event.target.value)"
                                   class="w-16 border-0 py-1 text-center text-sm focus:ring-0" aria-label="Cantidad de {{ $item->product->name }}">
                            <button type="button" wire:click="increment({{ $item->id }})" class="px-2.5 py-1 text-ink-soft hover:bg-sunken" aria-label="Añadir {{ $item->product->unit_type->step() }}">+</button>
                        </div>
                        <span class="w-6 text-xs text-ink-muted">{{ $item->product->unit_type->suffix() }}</span>

                        <p class="w-24 text-right font-medium">{{ Number::currency($item->line_total, 'EUR', 'es') }}</p>
                        <button type="button" wire:click="remove({{ $item->id }})" class="text-ink-muted hover:text-red-600" title="Eliminar">✕</button>
                    </li>
                @endforeach
            </ul>

            <x-card title="Resumen del pedido" class="h-fit">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt>Subtotal ({{ $items->count() }} {{ Str::plural('producto', $items->count()) }})</dt>
                        <dd class="font-medium">{{ Number::currency($pickup->subtotal, 'EUR', 'es') }}</dd>
                    </div>
                    <div class="flex justify-between text-ink-soft">
                        <dt>Recogida en tienda</dt>
                        <dd>Gratis</dd>
                    </div>
                    <div class="flex justify-between text-ink-soft">
                        <dt>Envío a domicilio</dt>
                        <dd>{{ $delivery->deliveryFee > 0 ? Number::currency($delivery->deliveryFee, 'EUR', 'es') : 'Gratis' }}</dd>
                    </div>
                    @if ($delivery->deliveryFee > 0)
                        <p class="rounded-control bg-action-soft px-2 py-1 text-xs text-action">
                            Te faltan {{ Number::currency($delivery->missingForFreeDelivery, 'EUR', 'es') }} para el envío gratis.
                        </p>
                    @endif
                    <div class="flex justify-between border-t border-line pt-3 text-base">
                        <dt class="font-semibold">Total (recogida)</dt>
                        <dd class="font-bold text-action" data-total>{{ Number::currency($pickup->total, 'EUR', 'es') }}</dd>
                    </div>
                    <div class="flex justify-between text-ink-soft">
                        <dt>Total con envío</dt>
                        <dd>{{ Number::currency($delivery->total, 'EUR', 'es') }}</dd>
                    </div>
                </dl>

                @if ($pickup->hasWeightedItems)
                    <p class="mt-3 rounded-control bg-warn-soft px-3 py-2 text-xs text-stock-low">
                        Incluye productos por peso: el importe es aproximado y se ajusta al pesarlos en tienda.
                    </p>
                @endif

                @if (Route::has('checkout.index'))
                    <a href="{{ route('checkout.index') }}" @class([
                        'mt-4 block rounded-control px-4 py-2.5 text-center text-sm font-medium',
                        'bg-action text-white hover:bg-action-hover' => empty($shortages),
                        'pointer-events-none bg-line text-ink-muted' => ! empty($shortages),
                    ])>Tramitar pedido</a>
                @endif
            </x-card>
        </div>
    @endif
</div>
