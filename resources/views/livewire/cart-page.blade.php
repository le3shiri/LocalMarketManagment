@use('Illuminate\Support\Number')
<div>
    <x-page-header title="Tu carrito" :subtitle="$items->isNotEmpty() ? $items->count().' '.Str::plural('producto', $items->count()).' · reservamos el stock al confirmar' : null">
        @if ($items->isNotEmpty())
            <x-slot:actions>
                <button type="button" wire:click="clear" wire:confirm="¿Vaciar el carrito?"
                        class="inline-flex min-h-11 items-center rounded-control px-3 text-sm font-bold text-ink-soft hover:bg-sunken hover:text-red-700">Vaciar carrito</button>
            </x-slot:actions>
        @endif
    </x-page-header>

    @if ($repeat = session('repeat'))
        @if ($repeat['adjusted'] || $repeat['skipped'])
            <div class="mb-5 rounded-control border border-stock-low/25 bg-warn-soft px-4 py-3 text-[15px] text-stock-low" role="status" data-repeat-report>
                @if ($repeat['adjusted'])
                    <p class="font-bold">Cantidades ajustadas a la disponibilidad:</p>
                    <ul class="mb-2 list-inside list-disc">@foreach ($repeat['adjusted'] as $line)<li>{{ $line }}</li>@endforeach</ul>
                @endif
                @if ($repeat['skipped'])
                    <p class="font-bold">No se han podido añadir:</p>
                    <ul class="list-inside list-disc">@foreach ($repeat['skipped'] as $line)<li>{{ $line }}</li>@endforeach</ul>
                @endif
            </div>
        @endif
    @endif

    @if ($error)
        <div class="mb-5 rounded-control border border-red-200 bg-red-50 px-4 py-3 text-[15px] font-semibold text-red-800" role="alert">{{ $error }}</div>
    @endif

    @if ($items->isEmpty())
        <section class="mx-auto max-w-xl rounded-shelf border border-line bg-card px-8 py-12 text-center">
            <div class="mx-auto grid h-16 w-16 place-items-center rounded-[20px] bg-sunken" aria-hidden="true">
                <svg class="h-7 w-7 text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7h14l-1.5 11h-11z"/><path d="M9 7a3 3 0 0 1 6 0"/></svg>
            </div>
            <h2 class="mt-5 text-2xl font-extrabold tracking-tight">Tu carrito está vacío.</h2>
            <p class="mt-2 text-base text-ink-soft">Pasa por las estanterías y añade lo que necesites.</p>
            <a href="{{ route('catalog.index') }}" class="mt-6 inline-flex h-12 items-center rounded-control bg-action px-6 text-[15px] font-bold text-on-action hover:bg-action-hover">Ir al catálogo</a>
        </section>
    @else
        <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
            <ul class="space-y-3 lg:col-span-2" wire:loading.class="opacity-70">
                @foreach ($items as $item)
                    @php($c = \App\Support\SectionColors::for($item->product->category?->slug))
                    <li class="flex flex-wrap items-center gap-4 rounded-card bg-card p-4 shadow-card" wire:key="line-{{ $item->id }}">
                        <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-control text-2xl font-extralight {{ $c['tint'] }} {{ $c['ink'] }}" aria-hidden="true">
                            @if ($item->product->image_url)
                                <img src="{{ $item->product->image_url }}" alt="" class="h-full w-full object-cover">
                            @else
                                {{ mb_substr($item->product->name, 0, 1) }}
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('catalog.show', $item->product) }}" class="text-[17px] font-bold text-ink hover:text-action">{{ $item->product->name }}</a>
                            <p class="text-[13px] text-ink-muted">{{ $item->product->formatted_price }}</p>
                            @isset($shortages[$item->id])
                                <p class="mt-1 text-[13px] font-bold text-red-700">
                                    @if ($shortages[$item->id] > 0)
                                        Solo quedan {{ $item->product->formatQuantity($shortages[$item->id]) }}: ajusta la cantidad.
                                    @else
                                        Se ha agotado mientras comprabas.
                                    @endif
                                </p>
                            @endisset
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex h-12 items-center rounded-control bg-sunken px-1">
                                <button type="button" wire:click="decrement({{ $item->id }})" class="grid h-10 w-10 place-items-center rounded-[10px] text-xl text-ink-soft hover:bg-card" aria-label="Quitar {{ $item->product->unit_type->step() }}">−</button>
                                <input type="number" value="{{ $item->quantity }}" step="{{ $item->product->unit_type->step() }}" min="0"
                                       wire:change="updateQuantity({{ $item->id }}, $event.target.value)"
                                       class="w-16 border-0 bg-transparent p-0 text-center text-[15px] font-bold focus:ring-0" aria-label="Cantidad de {{ $item->product->name }}">
                                <button type="button" wire:click="increment({{ $item->id }})" class="grid h-10 w-10 place-items-center rounded-[10px] text-xl text-ink-soft hover:bg-card" aria-label="Añadir {{ $item->product->unit_type->step() }}">+</button>
                            </div>
                            <span class="w-6 text-[13px] font-semibold text-ink-muted">{{ $item->product->unit_type->suffix() }}</span>
                        </div>

                        <p class="w-24 text-right text-lg font-extrabold tracking-tight">{{ Number::currency($item->line_total, 'EUR', 'es') }}</p>
                        <button type="button" wire:click="remove({{ $item->id }})" class="grid h-11 w-11 place-items-center rounded-control text-ink-muted hover:bg-red-50 hover:text-red-700" title="Eliminar" aria-label="Eliminar {{ $item->product->name }}">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                        </button>
                    </li>
                @endforeach
            </ul>

            <aside class="rounded-card bg-card p-6 shadow-card lg:sticky lg:top-6" aria-labelledby="resumen">
                <h2 id="resumen" class="text-xl font-extrabold tracking-tight">Resumen del pedido</h2>
                <dl class="mt-4 space-y-2.5 text-[15px]">
                    <div class="flex justify-between">
                        <dt>Subtotal ({{ $items->count() }} {{ Str::plural('producto', $items->count()) }})</dt>
                        <dd class="font-bold">{{ Number::currency($pickup->subtotal, 'EUR', 'es') }}</dd>
                    </div>
                    <div class="flex justify-between text-ink-soft">
                        <dt>Recogida en tienda</dt>
                        <dd class="font-semibold text-success-ink">Gratis</dd>
                    </div>
                    <div class="flex justify-between text-ink-soft">
                        <dt>Envío a domicilio</dt>
                        <dd>{{ $delivery->deliveryFee > 0 ? Number::currency($delivery->deliveryFee, 'EUR', 'es') : 'Gratis' }}</dd>
                    </div>
                    @if ($delivery->deliveryFee > 0)
                        <p class="rounded-control bg-action-soft px-3 py-2 text-[13px] font-semibold text-action">
                            Te faltan {{ Number::currency($delivery->missingForFreeDelivery, 'EUR', 'es') }} para el envío gratis.
                        </p>
                    @endif
                    <div class="flex items-baseline justify-between border-t border-line pt-3">
                        <dt class="font-bold">Total (recogida)</dt>
                        <dd class="text-[28px] font-extrabold tracking-tight text-ink" data-total>{{ Number::currency($pickup->total, 'EUR', 'es') }}</dd>
                    </div>
                    <div class="flex justify-between text-ink-soft">
                        <dt>Total con envío</dt>
                        <dd>{{ Number::currency($delivery->total, 'EUR', 'es') }}</dd>
                    </div>
                </dl>

                @if ($pickup->hasWeightedItems)
                    <p class="mt-4 rounded-control bg-warn-soft px-3 py-2 text-[13px] text-stock-low">
                        Incluye productos por peso: el importe es aproximado y se ajusta al pesarlos en tienda.
                    </p>
                @endif

                @if (Route::has('checkout.index'))
                    <a href="{{ route('checkout.index') }}" @if (! empty($shortages)) aria-disabled="true" tabindex="-1" @endif @class([
                        'mt-5 flex h-12 items-center justify-center rounded-control text-[15px] font-bold',
                        'bg-action text-on-action hover:bg-action-hover' => empty($shortages),
                        'pointer-events-none bg-sunken text-ink-muted' => ! empty($shortages),
                    ])>Tramitar pedido</a>
                @endif
                <p class="mt-3 text-center text-[13px] text-ink-muted">Pagas al recoger o recibir el pedido, en efectivo o con tarjeta.</p>
            </aside>
        </div>
    @endif
</div>
