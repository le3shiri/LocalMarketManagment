@use('Illuminate\Support\Number')
<div class="grid gap-6 lg:grid-cols-5">
    <div class="space-y-4 lg:col-span-3">
        <div class="flex items-center justify-between">
            <h1 class="text-[28px] font-extrabold tracking-tight text-ink">Caja</h1>
            <span class="text-sm text-ink-muted">{{ auth()->user()->name }} · {{ now()->format('d/m/Y') }}</span>
        </div>

        {{-- Scanners press Enter a few ms after the last digit, before the debounced model update
             reaches the server: the typed value is passed to scan() directly and the field is
             cleared here, so the late debounced update can't put the old code back. --}}
        <form x-data @submit.prevent="const code = $refs.search.value; $refs.search.value = ''; $wire.scan(code)" class="relative">
            <input type="search" wire:model.live.debounce.200ms="search" autofocus autocomplete="off" x-ref="search"
                   x-init="$el.focus()" @focus-search.window="$nextTick(() => $el.focus())"
                   placeholder="Escanea el código de barras o escribe el nombre / SKU y pulsa Enter"
                   class="block w-full rounded-card border-2 border-action px-4 py-3 text-lg shadow-card focus:outline-none focus:ring-2 focus:ring-action-ring"
                   aria-label="Buscar producto o escanear código">
            @if ($this->results->isNotEmpty())
                <ul class="absolute z-10 mt-1 w-full divide-y divide-line rounded-card border border-line bg-white shadow-lg">
                    @foreach ($this->results as $result)
                        <li wire:key="pos-result-{{ $result->id }}">
                            <button type="button" wire:click="add({{ $result->id }})" class="flex w-full items-center justify-between px-4 py-2 text-left hover:bg-action-soft">
                                <span>{{ $result->name }} <span class="font-mono text-xs text-ink-muted">{{ $result->sku }}</span></span>
                                <span class="text-sm text-ink-soft">{{ $result->formatted_price }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </form>
        @if ($message)
            <p class="rounded-control bg-warn-soft px-3 py-2 text-sm text-stock-low" role="alert">{{ $message }}</p>
        @endif

        <div class="overflow-hidden rounded-card border border-line bg-white">
            <table class="w-full text-sm">
                <thead class="bg-sunken text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted">
                    <tr>
                        <th class="px-4 py-2">Producto</th>
                        <th class="px-4 py-2 text-right">Precio</th>
                        <th class="px-4 py-2 text-center">Cantidad</th>
                        <th class="px-4 py-2 text-right">Importe</th>
                        <th class="w-8"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($this->totals->lines as $line)
                        @php($product = $line['product'])
                        <tr wire:key="pos-line-{{ $product->id }}">
                            <td class="px-4 py-2">
                                <span class="font-medium">{{ $product->name }}</span>
                                <span class="block text-xs text-ink-muted">IVA {{ $product->vat_rate }} %</span>
                            </td>
                            <td class="px-4 py-2 text-right">{{ $product->formatted_price }}</td>
                            <td class="px-4 py-2 text-center">
                                <input type="number" value="{{ $line['quantity'] }}" min="0"
                                       step="{{ $product->unit_type === \App\Enums\UnitType::Weight ? '0.005' : '1' }}"
                                       wire:change="setQuantity({{ $product->id }}, $event.target.value)"
                                       class="w-20 rounded-control border border-line-strong px-2 py-1 text-right" aria-label="Cantidad de {{ $product->name }}">
                                <span class="text-xs text-ink-muted">{{ $product->unit_type->suffix() }}</span>
                            </td>
                            <td class="px-4 py-2 text-right font-medium">{{ Number::currency($line['line_total'], 'EUR', 'es') }}</td>
                            <td class="pr-3 text-right">
                                <button type="button" wire:click="removeLine({{ $product->id }})" class="text-ink-muted hover:text-red-600" title="Quitar línea">✕</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-ink-muted">Escanea o busca un producto para empezar la venta.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <aside class="space-y-4 lg:col-span-2">
        <x-card>
            <p class="text-sm uppercase tracking-wide text-ink-muted">Total a cobrar</p>
            <p class="text-5xl font-bold text-ink" data-total>{{ Number::currency($this->totals->total, 'EUR', 'es') }}</p>

            @if (! $this->totals->isEmpty())
                <table class="mt-4 w-full text-xs text-ink-muted">
                    <thead><tr><th class="text-left">IVA</th><th class="text-right">Base</th><th class="text-right">Cuota</th><th class="text-right">Total</th></tr></thead>
                    <tbody>
                        @foreach ($this->totals->vat as $rate => $row)
                            <tr>
                                <td>{{ $rate }} %</td>
                                <td class="text-right">{{ Number::currency($row['base'], 'EUR', 'es') }}</td>
                                <td class="text-right">{{ Number::currency($row['vat'], 'EUR', 'es') }}</td>
                                <td class="text-right">{{ Number::currency($row['total'], 'EUR', 'es') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <button type="button" wire:click="clear" wire:confirm="¿Anular la venta en curso?" class="mt-4 text-sm text-ink-muted hover:text-red-600">Anular venta</button>
            @endif
        </x-card>

        @unless ($this->totals->isEmpty())
            <x-card title="Cobro">
                <div class="mb-4 grid grid-cols-2 gap-2">
                    @foreach (\App\Enums\PaymentMethod::cases() as $method)
                        <label @class([
                            'cursor-pointer rounded-control border-2 px-3 py-2 text-center font-medium',
                            'border-action bg-action-soft text-action' => $paymentMethod === $method->value,
                            'border-line text-ink-soft' => $paymentMethod !== $method->value,
                        ])>
                            <input type="radio" wire:model.live="paymentMethod" value="{{ $method->value }}" class="sr-only">{{ $method->label() }}
                        </label>
                    @endforeach
                </div>

                @if ($paymentMethod === 'cash')
                    <label for="tendered" class="block text-sm font-medium text-ink-soft">Entregado</label>
                    <input id="tendered" type="text" inputmode="decimal" wire:model.live.debounce.300ms="tendered"
                           class="mt-1 block w-full rounded-control border border-line-strong px-3 py-2 text-right text-xl">
                    @error('tendered') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    <div class="mt-2 grid grid-cols-5 gap-1 text-sm">
                        <button type="button" wire:click="exactAmount" class="rounded-control bg-sunken py-1.5 hover:bg-line">Exacto</button>
                        @foreach ([5, 10, 20, 50] as $note)
                            <button type="button" wire:click="addTendered({{ $note }})" class="rounded-control bg-sunken py-1.5 hover:bg-line">+{{ $note }} €</button>
                        @endforeach
                    </div>
                    @if (! is_null($this->change))
                        <p @class(['mt-3 flex justify-between text-lg font-semibold', 'text-action' => $this->change >= 0, 'text-red-600' => $this->change < 0])>
                            <span>{{ $this->change >= 0 ? 'Cambio' : 'Falta' }}</span>
                            <span data-change>{{ Number::currency(abs($this->change), 'EUR', 'es') }}</span>
                        </p>
                    @endif
                @else
                    <p class="mb-2 text-sm text-ink-muted">Cobra en el datáfono y, si quieres, anota el código de autorización del recibo.</p>
                    <label for="authCode" class="block text-sm font-medium text-ink-soft">Código de autorización (opcional)</label>
                    <input id="authCode" type="text" wire:model="authCode" maxlength="20" autocomplete="off"
                           class="mt-1 block w-full rounded-control border border-line-strong px-3 py-2 font-mono uppercase">
                    @error('authCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @endif

                <button type="button" wire:click="confirmSale" wire:loading.attr="disabled"
                        class="mt-4 w-full rounded-card bg-action py-3 text-lg font-semibold text-white hover:bg-action-hover disabled:opacity-50">
                    Cobrar {{ Number::currency($this->totals->total, 'EUR', 'es') }}
                </button>
            </x-card>
        @endunless
    </aside>
</div>
