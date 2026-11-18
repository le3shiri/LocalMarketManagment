@use('Illuminate\Support\Number')
<div>
    <a href="{{ route('staff.orders.index') }}" class="text-sm text-stone-500 hover:underline">← Volver a pedidos</a>
    <h1 class="mb-6 mt-2 text-2xl font-bold text-stone-900">
        {{ $channel === 'whatsapp' ? 'Nuevo pedido por WhatsApp' : 'Nuevo pedido por teléfono' }}
    </h1>

    @if ($error)
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $error }}</div>
    @endif

    @if ($duplicates)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">
            <p>
                Posible pedido duplicado: este teléfono ya tiene
                @foreach ($duplicates as $code)
                    <a href="{{ route('staff.orders.show', $code) }}" class="font-mono font-medium underline" target="_blank">{{ $code }}</a>@if (! $loop->last), @endif
                @endforeach
                en la misma franja.
            </p>
            <button type="button" wire:click="saveAnyway" class="rounded-lg bg-amber-600 px-3 py-1.5 font-medium text-white hover:bg-amber-700">Es otro pedido, registrar igualmente</button>
        </div>
    @endif

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Cliente">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <label for="contact_phone" class="block text-sm font-medium text-stone-700">Teléfono</label>
                        <input id="contact_phone" type="tel" wire:model.live.debounce.400ms="contact_phone" autofocus
                               class="block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm">
                        @error('contact_phone') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        @if ($this->customer)
                            <p class="text-xs text-emerald-700">Cliente registrado: {{ $this->customer->name }} · el pedido aparecerá en su cuenta.</p>
                        @elseif (strlen(\App\Models\User::normalizePhone($contact_phone)) >= 9)
                            <p class="text-xs text-stone-500">Sin cuenta: el pedido se guarda con nombre y teléfono.</p>
                        @endif
                    </div>
                    <div class="space-y-1">
                        <label for="contact_name" class="block text-sm font-medium text-stone-700">Nombre</label>
                        <input id="contact_name" type="text" wire:model="contact_name" class="block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm">
                        @error('contact_name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    @includeWhen($channel === 'whatsapp', 'livewire.staff.partials.whatsapp-fields')
                </div>
            </x-card>

            <x-card title="Productos">
                <div class="relative">
                    <input type="search" wire:model.live.debounce.250ms="search" placeholder="Buscar producto por nombre o SKU…"
                           class="block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Buscar producto">
                    @if ($this->results->isNotEmpty())
                        <ul class="absolute z-10 mt-1 w-full divide-y divide-stone-100 rounded-lg border border-stone-200 bg-white shadow-lg">
                            @foreach ($this->results as $result)
                                <li wire:key="result-{{ $result->id }}">
                                    <button type="button" wire:click="addProduct({{ $result->id }})" @disabled(! $result->availability()->canBuy())
                                            class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50">
                                        <span>{{ $result->name }} <span class="font-mono text-xs text-stone-400">{{ $result->sku }}</span></span>
                                        <span class="text-xs text-stone-500">{{ $result->formatted_price }} · quedan {{ $result->formatQuantity($result->stock->available_online) }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <ul class="mt-4 divide-y divide-stone-100 text-sm">
                    @forelse ($items as $productId => $item)
                        <li class="flex items-center gap-3 py-2" wire:key="line-{{ $productId }}">
                            <span class="flex-1">{{ $item->product->name }}</span>
                            <input type="number" value="{{ $item->quantity }}" step="{{ $item->product->unit_type->step() }}" min="0"
                                   wire:change="setQuantity({{ $productId }}, $event.target.value)"
                                   class="w-20 rounded-lg border border-stone-300 px-2 py-1 text-right text-sm" aria-label="Cantidad de {{ $item->product->name }}">
                            <span class="w-6 text-xs text-stone-500">{{ $item->product->unit_type->suffix() }}</span>
                            <span class="w-20 text-right">{{ Number::currency($item->line_total, 'EUR', 'es') }}</span>
                            <button type="button" wire:click="removeLine({{ $productId }})" class="text-stone-400 hover:text-red-600" title="Quitar">✕</button>
                        </li>
                    @empty
                        <li class="py-6 text-center text-stone-500">Busca y añade los productos que pide el cliente.</li>
                    @endforelse
                </ul>
            </x-card>

            <x-card title="Entrega">
                <div class="mb-4 flex gap-2">
                    @foreach (\App\Enums\Fulfillment::cases() as $option)
                        <label @class([
                            'cursor-pointer rounded-lg border px-3 py-2 text-sm',
                            'border-emerald-600 bg-emerald-50 font-medium text-emerald-800' => $fulfillment === $option->value,
                            'border-stone-300' => $fulfillment !== $option->value,
                        ])>
                            <input type="radio" wire:model.live="fulfillment" value="{{ $option->value }}" class="sr-only">{{ $option->label() }}
                        </label>
                    @endforeach
                </div>
                @if ($fulfillment === 'delivery')
                    <div class="mb-4 space-y-1">
                        <label for="delivery_address" class="block text-sm font-medium text-stone-700">Dirección de entrega</label>
                        <input id="delivery_address" type="text" wire:model="delivery_address" class="block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm">
                        @error('delivery_address') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="mb-3 flex flex-wrap gap-2">
                    @foreach ($days as $day)
                        <button type="button" wire:click="$set('slot_date', '{{ $day->toDateString() }}')" @class([
                            'rounded-lg border px-3 py-1.5 text-sm',
                            'border-emerald-600 bg-emerald-600 text-white' => $slot_date === $day->toDateString(),
                            'border-stone-300 bg-white' => $slot_date !== $day->toDateString(),
                        ])>{{ $day->isToday() ? 'Hoy' : ucfirst($day->locale('es')->isoFormat('ddd D')) }}</button>
                    @endforeach
                </div>
                <select wire:model="slot" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Franja horaria">
                    <option value="">Elige franja…</option>
                    @foreach ($availableSlots as $option => $free)
                        <option value="{{ $option }}" @disabled($free === 0)>{{ $option }} ({{ $free === 0 ? 'completa' : "{$free} huecos" }})</option>
                    @endforeach
                </select>
                @error('slot') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                <div class="mt-4 flex items-center gap-3 text-sm">
                    <span class="font-medium text-stone-700">Pagará con</span>
                    @foreach (\App\Enums\PaymentMethod::cases() as $method)
                        <label class="flex items-center gap-1">
                            <input type="radio" wire:model="payment_method" value="{{ $method->value }}" class="text-emerald-600"> {{ $method->label() }}
                        </label>
                    @endforeach
                </div>

                <div class="mt-4 space-y-1">
                    <label for="notes" class="block text-sm font-medium text-stone-700">Observaciones</label>
                    <textarea id="notes" wire:model="notes" rows="2" class="block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm"></textarea>
                </div>
            </x-card>
        </div>

        <x-card title="Resumen" class="h-fit">
            <dl class="space-y-1 text-sm">
                <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ Number::currency($totals->subtotal, 'EUR', 'es') }}</dd></div>
                <div class="flex justify-between"><dt>{{ $fulfillment === 'delivery' ? 'Envío' : 'Recogida' }}</dt><dd>{{ $totals->deliveryFee > 0 ? Number::currency($totals->deliveryFee, 'EUR', 'es') : 'Gratis' }}</dd></div>
                <div class="flex justify-between border-t border-stone-200 pt-2 text-base font-semibold"><dt>Total</dt><dd class="text-emerald-700">{{ Number::currency($totals->total, 'EUR', 'es') }}</dd></div>
            </dl>
            @if ($totals->hasWeightedItems)
                <p class="mt-2 text-xs text-amber-700">Recuerda avisar de que los productos por peso son aproximados.</p>
            @endif
            <x-button class="mt-4 w-full" wire:loading.attr="disabled">Registrar pedido</x-button>
        </x-card>
    </form>
</div>
