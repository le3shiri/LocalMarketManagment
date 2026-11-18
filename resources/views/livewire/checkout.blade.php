@use('Illuminate\Support\Number')
<div>
    <a href="{{ route('cart.index') }}" class="text-sm text-stone-500 hover:underline">← Volver al carrito</a>
    <h1 class="mb-6 mt-2 text-2xl font-bold text-stone-900">Tramitar pedido</h1>

    @if ($error)
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $error }}</div>
    @endif

    <form wire:submit="placeOrder" class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="1. ¿Cómo quieres recibirlo?">
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($fulfillments as $option)
                        <label @class([
                            'cursor-pointer rounded-xl border-2 p-4 transition',
                            'border-emerald-600 bg-emerald-50' => $fulfillment === $option->value,
                            'border-stone-200 hover:border-stone-300' => $fulfillment !== $option->value,
                        ])>
                            <input type="radio" wire:model.live="fulfillment" value="{{ $option->value }}" class="sr-only">
                            <span class="block font-medium text-stone-900">{{ $option->label() }}</span>
                            <span class="text-sm text-stone-500">
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
                    <div class="mt-4 space-y-1">
                        <label for="delivery_address" class="block text-sm font-medium text-stone-700">Dirección de entrega</label>
                        <input id="delivery_address" type="text" wire:model="delivery_address" placeholder="Calle, número, piso y código postal"
                               class="block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm">
                        @error('delivery_address') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif
            </x-card>

            <x-card :title="$fulfillment === 'delivery' ? '2. Franja de entrega' : '2. Franja de recogida'">
                <div class="mb-4 flex flex-wrap gap-2">
                    @foreach ($days as $day)
                        <button type="button" wire:click="$set('slot_date', '{{ $day->toDateString() }}')" @class([
                            'rounded-lg border px-3 py-2 text-sm',
                            'border-emerald-600 bg-emerald-600 text-white' => $slot_date === $day->toDateString(),
                            'border-stone-300 bg-white text-stone-700 hover:border-emerald-600' => $slot_date !== $day->toDateString(),
                        ])>{{ $day->isToday() ? 'Hoy' : ($day->isTomorrow() ? 'Mañana' : ucfirst($day->locale('es')->isoFormat('ddd D MMM'))) }}</button>
                    @endforeach
                </div>
                @error('slot_date') <p class="mb-2 text-xs text-red-600">{{ $message }}</p> @enderror

                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @forelse ($availableSlots as $option => $free)
                        <label wire:key="slot-{{ $slot_date }}-{{ $option }}" @class([
                            'rounded-lg border px-3 py-2 text-center text-sm',
                            'cursor-not-allowed border-stone-200 bg-stone-50 text-stone-400' => $free === 0,
                            'cursor-pointer border-emerald-600 bg-emerald-50 font-medium text-emerald-800' => $free > 0 && $slot === $option,
                            'cursor-pointer border-stone-300 hover:border-emerald-600' => $free > 0 && $slot !== $option,
                        ])>
                            <input type="radio" wire:model.live="slot" value="{{ $option }}" class="sr-only" @disabled($free === 0)>
                            {{ $option }}
                            <span class="block text-xs font-normal">{{ $free === 0 ? 'Completa' : "{$free} huecos" }}</span>
                        </label>
                    @empty
                        <p class="col-span-full text-sm text-stone-500">No quedan franjas para este día.</p>
                    @endforelse
                </div>
                @error('slot') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </x-card>

            <x-card title="3. Datos de contacto">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <label for="contact_name" class="block text-sm font-medium text-stone-700">Nombre</label>
                        <input id="contact_name" type="text" wire:model="contact_name" class="block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm">
                        @error('contact_name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-1">
                        <label for="contact_phone" class="block text-sm font-medium text-stone-700">Teléfono</label>
                        <input id="contact_phone" type="tel" wire:model="contact_phone" class="block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm">
                        @error('contact_phone') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-1 sm:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-stone-700">Observaciones (opcional)</label>
                        <textarea id="notes" wire:model="notes" rows="2" placeholder="Ej.: tomates no muy maduros" class="block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm"></textarea>
                    </div>
                </div>
            </x-card>
        </div>

        <x-card title="Tu pedido" class="h-fit">
            <ul class="mb-4 divide-y divide-stone-100 text-sm">
                @foreach ($items as $item)
                    <li class="flex justify-between gap-2 py-2" wire:key="sum-{{ $item->id }}">
                        <span>{{ $item->product->formatQuantity($item->quantity) }} · {{ $item->product->name }}</span>
                        <span class="whitespace-nowrap">{{ Number::currency($item->line_total, 'EUR', 'es') }}</span>
                    </li>
                @endforeach
            </ul>
            <dl class="space-y-1 border-t border-stone-200 pt-3 text-sm">
                <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ Number::currency($totals->subtotal, 'EUR', 'es') }}</dd></div>
                <div class="flex justify-between"><dt>{{ $fulfillment === 'delivery' ? 'Envío' : 'Recogida' }}</dt><dd>{{ $totals->deliveryFee > 0 ? Number::currency($totals->deliveryFee, 'EUR', 'es') : 'Gratis' }}</dd></div>
                <div class="flex justify-between pt-2 text-base font-semibold"><dt>Total</dt><dd class="text-emerald-700">{{ Number::currency($totals->total, 'EUR', 'es') }}</dd></div>
            </dl>
            @if ($totals->hasWeightedItems)
                <p class="mt-3 text-xs text-amber-700">Los productos por peso se ajustan al pesarlos en tienda.</p>
            @endif
            <p class="mt-3 text-xs text-stone-500">El pago se realiza al recoger o recibir el pedido.</p>
            <x-button class="mt-4 w-full" wire:loading.attr="disabled">Confirmar pedido</x-button>
        </x-card>
    </form>
</div>
