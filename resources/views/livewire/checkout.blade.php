@use('Illuminate\Support\Number')
<div>
    <x-page-header title="Tramitar pedido" subtitle="Cuatro pasos y listo: reservamos tu pedido al confirmarlo." :back="route('cart.index')" back-label="Volver al carrito" />

    @if ($error)
        <div class="mb-4 rounded-control border border-red-200 bg-red-50 px-4 py-3 text-[15px] font-semibold text-red-800" role="alert">{{ $error }}</div>
    @endif

    <form wire:submit="placeOrder" class="grid gap-6 lg:grid-cols-3 lg:items-start">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="1. ¿Cómo quieres recibirlo?">
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($fulfillments as $option)
                        <label @class([
                            'block cursor-pointer rounded-card border-2 p-4 transition has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-action-ring',
                            'border-action bg-action-soft' => $fulfillment === $option->value,
                            'border-line hover:border-line-strong' => $fulfillment !== $option->value,
                        ])>
                            <input type="radio" wire:model.live="fulfillment" value="{{ $option->value }}" class="sr-only">
                            <span class="block text-[17px] font-bold text-ink">{{ $option->label() }}</span>
                            <span class="mt-0.5 block text-sm text-ink-muted">
                                @if ($option === \App\Enums\Fulfillment::Pickup)
                                    Gratis · Recoge en la tienda en la franja que elijas
                                @else
                                    {{ Number::currency(config('shop.delivery_fee'), 'EUR', 'es') }} · gratis desde {{ Number::currency(config('shop.free_delivery_from'), 'EUR', 'es') }}
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>

                @if ($fulfillment === 'delivery')
                    <div class="mt-4 space-y-1.5">
                        <label for="delivery_address" class="block text-sm font-semibold text-ink-soft">Dirección de entrega</label>
                        <input id="delivery_address" type="text" wire:model="delivery_address" placeholder="Calle, número, piso y código postal"
                               class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
                        @error('delivery_address') <p class="text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror
                    </div>
                @endif
            </x-card>

            <x-card :title="$fulfillment === 'delivery' ? '2. Franja de entrega' : '2. Franja de recogida'">
                <div class="mb-4 flex flex-wrap gap-2">
                    @foreach ($days as $day)
                        <button type="button" wire:click="$set('slot_date', '{{ $day->toDateString() }}')" @class([
                            'inline-flex min-h-11 items-center rounded-full border px-4 text-sm font-bold',
                            'border-action bg-action text-on-action' => $slot_date === $day->toDateString(),
                            'border-line-strong bg-card text-ink-soft hover:border-action' => $slot_date !== $day->toDateString(),
                        ])>{{ $day->isToday() ? 'Hoy' : ($day->isTomorrow() ? 'Mañana' : ucfirst($day->locale('es')->isoFormat('ddd D MMM'))) }}</button>
                    @endforeach
                </div>
                @error('slot_date') <p class="mb-2 text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @forelse ($availableSlots as $option => $free)
                        <label wire:key="slot-{{ $slot_date }}-{{ $option }}" @class([
                            'flex min-h-14 flex-col items-center justify-center rounded-control border-2 px-3 py-2 text-center text-[15px] font-bold has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-action-ring',
                            'cursor-not-allowed border-line bg-sunken text-ink-muted' => $free === 0,
                            'cursor-pointer border-action bg-action-soft text-action' => $free > 0 && $slot === $option,
                            'cursor-pointer border-line-strong hover:border-action' => $free > 0 && $slot !== $option,
                        ])>
                            <input type="radio" wire:model.live="slot" value="{{ $option }}" class="sr-only" @disabled($free === 0)>
                            {{ $option }}
                            <span class="block text-xs font-semibold opacity-80">{{ $free === 0 ? 'Completa' : "{$free} huecos" }}</span>
                        </label>
                    @empty
                        <p class="col-span-full text-sm text-ink-muted">No quedan franjas para este día.</p>
                    @endforelse
                </div>
                @error('slot') <p class="mt-2 text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror
            </x-card>

            <x-card title="3. ¿Cómo vas a pagar?">
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (\App\Enums\PaymentMethod::cases() as $method)
                        <label @class([
                            'block cursor-pointer rounded-card border-2 p-4 transition has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-action-ring',
                            'border-action bg-action-soft' => $payment_method === $method->value,
                            'border-line hover:border-line-strong' => $payment_method !== $method->value,
                        ])>
                            <input type="radio" wire:model.live="payment_method" value="{{ $method->value }}" class="sr-only">
                            <span class="block text-[17px] font-bold text-ink">{{ $method->label() }}</span>
                            <span class="mt-0.5 block text-sm text-ink-muted">{{ $fulfillment === 'delivery' ? 'Al recibir el pedido' : 'Al recoger en la tienda' }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-3 text-[13px] text-ink-muted">Pronto podrás pagar online. Por ahora se paga en la entrega, con el importe final de los productos por peso.</p>
                @error('payment_method') <p class="mt-1 text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror
            </x-card>

            <x-card title="4. Datos de contacto">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="contact_name" class="block text-sm font-semibold text-ink-soft">Nombre</label>
                        <input id="contact_name" type="text" wire:model="contact_name" class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
                        @error('contact_name') <p class="text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label for="contact_phone" class="block text-sm font-semibold text-ink-soft">Teléfono</label>
                        <input id="contact_phone" type="tel" wire:model="contact_phone" class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
                        @error('contact_phone') <p class="text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-1.5 sm:col-span-2">
                        <label for="notes" class="block text-sm font-semibold text-ink-soft">Observaciones (opcional)</label>
                        <textarea id="notes" wire:model="notes" rows="2" placeholder="Ej.: tomates no muy maduros" class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 py-2.5 text-[15px]"></textarea>
                    </div>
                </div>
            </x-card>
        </div>

        <x-card title="Tu pedido" class="h-fit lg:sticky lg:top-6">
            <ul class="mb-4 divide-y divide-line text-[15px]">
                @foreach ($items as $item)
                    <li class="flex justify-between gap-2 py-2" wire:key="sum-{{ $item->id }}">
                        <span>{{ $item->product->formatQuantity($item->quantity) }} · {{ $item->product->name }}</span>
                        <span class="whitespace-nowrap">{{ Number::currency($item->line_total, 'EUR', 'es') }}</span>
                    </li>
                @endforeach
            </ul>
            <dl class="space-y-1.5 border-t border-line pt-3 text-[15px]">
                <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ Number::currency($totals->subtotal, 'EUR', 'es') }}</dd></div>
                <div class="flex justify-between"><dt>{{ $fulfillment === 'delivery' ? 'Envío' : 'Recogida' }}</dt><dd>{{ $totals->deliveryFee > 0 ? Number::currency($totals->deliveryFee, 'EUR', 'es') : 'Gratis' }}</dd></div>
                <div class="flex items-baseline justify-between pt-2"><dt class="font-bold">Total</dt><dd class="text-[28px] font-extrabold tracking-tight text-ink">{{ Number::currency($totals->total, 'EUR', 'es') }}</dd></div>
            </dl>
            @if ($totals->hasWeightedItems)
                <p class="mt-3 rounded-control bg-warn-soft px-3 py-2 text-[13px] text-stock-low">Los productos por peso se ajustan al pesarlos en tienda.</p>
            @endif
            <p class="mt-3 text-[13px] text-ink-muted">El pago se realiza al recoger o recibir el pedido.</p>
            <x-button class="mt-4 h-12 w-full" wire:loading.attr="disabled">Confirmar pedido</x-button>
        </x-card>
    </form>
</div>
