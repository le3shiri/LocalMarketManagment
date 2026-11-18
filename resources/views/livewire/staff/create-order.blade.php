@use('Illuminate\Support\Number')
<div>
    <a href="{{ route('staff.orders.index') }}" class="inline-flex min-h-11 items-center text-[15px] font-bold text-action hover:underline">← Volver a pedidos</a>
    <h1 class="mb-6 mt-2 text-[28px] font-extrabold tracking-tight text-ink">
        {{ $channel === 'whatsapp' ? 'Nuevo pedido por WhatsApp' : 'Nuevo pedido por teléfono' }}
    </h1>

    @if ($error)
        <div class="mb-4 rounded-control border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $error }}</div>
    @endif

    @if ($duplicates)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-control border border-stock-low/40 bg-warn-soft px-4 py-3 text-sm text-stock-low" role="alert">
            <p>
                Posible pedido duplicado: este teléfono ya tiene
                @foreach ($duplicates as $code)
                    <a href="{{ route('staff.orders.show', $code) }}" class="font-mono font-medium underline" target="_blank">{{ $code }}</a>@if (! $loop->last), @endif
                @endforeach
                en la misma franja.
            </p>
            <button type="button" wire:click="saveAnyway" class="rounded-control bg-stock-low px-3 py-1.5 font-medium text-white hover:bg-stock-low-hover">Es otro pedido, registrar igualmente</button>
        </div>
    @endif

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Cliente">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1">
                        <label for="contact_phone" class="block text-sm font-semibold text-ink-soft">Teléfono</label>
                        <input id="contact_phone" type="tel" wire:model.live.debounce.400ms="contact_phone" autofocus
                               class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
                        @error('contact_phone') <p class="text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror
                        @if ($this->customer)
                            <p class="text-[13px] font-semibold text-success-ink">Cliente registrado: {{ $this->customer->name }} · el pedido aparecerá en su cuenta.</p>
                        @elseif (strlen(\App\Models\User::normalizePhone($contact_phone)) >= 9)
                            <p class="text-xs text-ink-muted">Sin cuenta: el pedido se guarda con nombre y teléfono.</p>
                        @endif
                    </div>
                    <div class="space-y-1">
                        <label for="contact_name" class="block text-sm font-semibold text-ink-soft">Nombre</label>
                        <input id="contact_name" type="text" wire:model="contact_name" class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
                        @error('contact_name') <p class="text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror
                    </div>
                    @includeWhen($channel === 'whatsapp', 'livewire.staff.partials.whatsapp-fields')
                </div>
            </x-card>

            <x-card title="Productos">
                <div class="relative">
                    <input type="search" wire:model.live.debounce.250ms="search" placeholder="Buscar producto por nombre o SKU…"
                           class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Buscar producto">
                    @if ($this->results->isNotEmpty())
                        <ul class="absolute z-10 mt-1 w-full divide-y divide-line rounded-control border border-line bg-card shadow-lg">
                            @foreach ($this->results as $result)
                                <li wire:key="result-{{ $result->id }}">
                                    <button type="button" wire:click="addProduct({{ $result->id }})" @disabled(! $result->availability()->canBuy())
                                            class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-action-soft disabled:cursor-not-allowed disabled:opacity-50">
                                        <span>{{ $result->name }} <span class="font-mono text-xs text-ink-muted">{{ $result->sku }}</span></span>
                                        <span class="text-xs text-ink-muted">{{ $result->formatted_price }} · quedan {{ $result->formatQuantity($result->stock->available_online) }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <ul class="mt-4 divide-y divide-line text-sm">
                    @forelse ($items as $productId => $item)
                        <li class="flex items-center gap-3 py-2" wire:key="line-{{ $productId }}">
                            <span class="flex-1">{{ $item->product->name }}</span>
                            <input type="number" value="{{ $item->quantity }}" step="{{ $item->product->unit_type->step() }}" min="0"
                                   wire:change="setQuantity({{ $productId }}, $event.target.value)"
                                   class="w-20 rounded-control border border-line-strong px-2 py-1 text-right text-sm" aria-label="Cantidad de {{ $item->product->name }}">
                            <span class="w-6 text-xs text-ink-muted">{{ $item->product->unit_type->suffix() }}</span>
                            <span class="w-20 text-right">{{ Number::currency($item->line_total, 'EUR', 'es') }}</span>
                            <button type="button" wire:click="removeLine({{ $productId }})" class="text-ink-muted hover:text-red-700" title="Quitar">✕</button>
                        </li>
                    @empty
                        <li class="py-6 text-center text-ink-muted">Busca y añade los productos que pide el cliente.</li>
                    @endforelse
                </ul>
            </x-card>

            <x-card title="Entrega">
                <div class="mb-4 flex gap-2">
                    @foreach (\App\Enums\Fulfillment::cases() as $option)
                        <label @class([
                            'cursor-pointer rounded-control border px-3 py-2 text-sm',
                            'border-action bg-action-soft font-medium text-action' => $fulfillment === $option->value,
                            'border-line-strong' => $fulfillment !== $option->value,
                        ])>
                            <input type="radio" wire:model.live="fulfillment" value="{{ $option->value }}" class="sr-only">{{ $option->label() }}
                        </label>
                    @endforeach
                </div>
                @if ($fulfillment === 'delivery')
                    <div class="mb-4 space-y-1">
                        <label for="delivery_address" class="block text-sm font-semibold text-ink-soft">Dirección de entrega</label>
                        <input id="delivery_address" type="text" wire:model="delivery_address" class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
                        @error('delivery_address') <p class="text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="mb-3 flex flex-wrap gap-2">
                    @foreach ($days as $day)
                        <button type="button" wire:click="$set('slot_date', '{{ $day->toDateString() }}')" @class([
                            'rounded-control border px-3 py-1.5 text-sm',
                            'border-ink bg-ink text-on-action' => $slot_date === $day->toDateString(),
                            'border-line-strong bg-card' => $slot_date !== $day->toDateString(),
                        ])>{{ $day->isToday() ? 'Hoy' : ucfirst($day->locale('es')->isoFormat('ddd D')) }}</button>
                    @endforeach
                </div>
                <select wire:model="slot" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Franja horaria">
                    <option value="">Elige franja…</option>
                    @foreach ($availableSlots as $option => $free)
                        <option value="{{ $option }}" @disabled($free === 0)>{{ $option }} ({{ $free === 0 ? 'completa' : "{$free} huecos" }})</option>
                    @endforeach
                </select>
                @error('slot') <p class="mt-1 text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror

                <div class="mt-4 flex items-center gap-3 text-sm">
                    <span class="font-medium text-ink-soft">Pagará con</span>
                    @foreach (\App\Enums\PaymentMethod::cases() as $method)
                        <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 pr-3 font-semibold">
                            <input type="radio" wire:model="payment_method" value="{{ $method->value }}" class="accent-action"> {{ $method->label() }}
                        </label>
                    @endforeach
                </div>

                <div class="mt-4 space-y-1">
                    <label for="notes" class="block text-sm font-semibold text-ink-soft">Observaciones</label>
                    <textarea id="notes" wire:model="notes" rows="2" class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]"></textarea>
                </div>
            </x-card>
        </div>

        <x-card title="Resumen" class="h-fit">
            <dl class="space-y-1 text-sm">
                <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ Number::currency($totals->subtotal, 'EUR', 'es') }}</dd></div>
                <div class="flex justify-between"><dt>{{ $fulfillment === 'delivery' ? 'Envío' : 'Recogida' }}</dt><dd>{{ $totals->deliveryFee > 0 ? Number::currency($totals->deliveryFee, 'EUR', 'es') : 'Gratis' }}</dd></div>
                <div class="flex items-baseline justify-between border-t border-line pt-3"><dt class="font-bold">Total</dt><dd class="text-2xl font-extrabold tracking-tight text-ink">{{ Number::currency($totals->total, 'EUR', 'es') }}</dd></div>
            </dl>
            @if ($totals->hasWeightedItems)
                <p class="mt-2 text-xs text-stock-low">Recuerda avisar de que los productos por peso son aproximados.</p>
            @endif
            <x-button class="mt-4 w-full" wire:loading.attr="disabled">Registrar pedido</x-button>
        </x-card>
    </form>
</div>
