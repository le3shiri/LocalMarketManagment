@use('Illuminate\Support\Number')
<div class="grid gap-6 lg:grid-cols-5">
    <div class="space-y-4 lg:col-span-3">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <h1 class="text-[28px] font-extrabold tracking-tight text-ink">Caja</h1>
            <span class="rounded-full bg-sunken px-3 py-1 text-[13px] font-semibold text-ink-soft">{{ auth()->user()->name }} · {{ now()->format('d/m/Y') }}</span>
        </div>

        {{-- Scanners press Enter a few ms after the last digit, before the debounced model update
             reaches the server: the typed value is passed to scan() directly and the field is
             cleared here, so the late debounced update can't put the old code back. --}}
        <form x-data @submit.prevent="const code = $refs.search.value; $refs.search.value = ''; $wire.scan(code)" class="relative">
            <svg class="pointer-events-none absolute left-4 top-1/2 h-6 w-6 -translate-y-1/2 text-action" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 6v12M8 6v12M11 6v12M15 6v12M18 6v12M21 6v12"/></svg>
            <input type="search" wire:model.live.debounce.200ms="search" autofocus autocomplete="off" x-ref="search"
                   x-init="$el.focus()" @focus-search.window="$nextTick(() => $el.focus())"
                   placeholder="Escanea el código de barras o escribe el nombre / SKU y pulsa Enter"
                   class="block h-14 w-full rounded-card border-2 border-action bg-card pl-14 pr-4 text-lg font-semibold shadow-card placeholder:font-normal placeholder:text-ink-muted focus:outline-none"
                   aria-label="Buscar producto o escanear código">
            @if ($this->results->isNotEmpty())
                <ul class="absolute z-10 mt-1 w-full divide-y divide-line rounded-card border border-line bg-card shadow-lg">
                    @foreach ($this->results as $result)
                        <li wire:key="pos-result-{{ $result->id }}">
                            <button type="button" wire:click="add({{ $result->id }})" class="flex min-h-12 w-full items-center justify-between px-4 text-left hover:bg-action-soft">
                                <span class="font-semibold">{{ $result->name }} <span class="font-mono text-xs font-normal text-ink-muted">{{ $result->sku }}</span></span>
                                <span class="text-sm text-ink-soft">{{ $result->formatted_price }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </form>
        @if ($message)
            <p class="rounded-control border border-stock-low/25 bg-warn-soft px-4 py-3 text-[15px] font-semibold text-stock-low" role="alert">{{ $message }}</p>
        @endif

        <div class="overflow-x-auto rounded-card bg-card shadow-card">
            <table class="w-full text-[15px]">
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
                                <span class="font-bold">{{ $product->name }}</span>
                                <span class="block text-xs text-ink-muted">IVA {{ $product->vat_rate }} %</span>
                            </td>
                            <td class="px-4 py-2 text-right">{{ $product->formatted_price }}</td>
                            <td class="px-4 py-2 text-center">
                                <input type="number" value="{{ $line['quantity'] }}" min="0"
                                       step="{{ $product->unit_type === \App\Enums\UnitType::Weight ? '0.005' : '1' }}"
                                       wire:change="setQuantity({{ $product->id }}, $event.target.value)"
                                       class="h-11 w-24 rounded-control border border-line-strong bg-card px-2 text-right font-bold" aria-label="Cantidad de {{ $product->name }}">
                                <span class="text-xs text-ink-muted">{{ $product->unit_type->suffix() }}</span>
                            </td>
                            <td class="px-4 py-2 text-right font-extrabold">{{ Number::currency($line['line_total'], 'EUR', 'es') }}</td>
                            <td class="pr-3 text-right">
                                <button type="button" wire:click="removeLine({{ $product->id }})" class="grid h-11 w-11 place-items-center rounded-control text-ink-muted hover:bg-red-50 hover:text-red-700" title="Quitar línea" aria-label="Quitar {{ $product->name }}"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
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
            <p class="text-[13px] font-bold uppercase tracking-[0.06em] text-ink-muted">Total a cobrar</p>
            <p class="mt-1 text-5xl font-extrabold tracking-[-0.03em] text-ink" data-total>{{ Number::currency($this->totals->total, 'EUR', 'es') }}</p>

            @if (! $this->totals->isEmpty())
                <table class="mt-4 w-full border-t border-line pt-2 text-[13px] text-ink-soft">
                    <thead class="text-ink-muted"><tr><th class="pt-2 text-left font-bold">IVA</th><th class="text-right">Base</th><th class="text-right">Cuota</th><th class="text-right">Total</th></tr></thead>
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
                <button type="button" wire:click="clear" wire:confirm="¿Anular la venta en curso?" class="mt-4 inline-flex min-h-11 items-center rounded-control border border-red-200 px-4 text-sm font-bold text-red-700 hover:bg-red-50">Anular venta</button>
            @endif
        </x-card>

        @unless ($this->totals->isEmpty())
            <x-card title="Cobro">
                <div class="mb-4 grid grid-cols-2 gap-2">
                    @foreach (\App\Enums\PaymentMethod::cases() as $method)
                        <label @class([
                            'flex min-h-14 cursor-pointer items-center justify-center rounded-control border-2 px-3 text-center text-[17px] font-bold has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-action-ring',
                            'border-action bg-action-soft text-action' => $paymentMethod === $method->value,
                            'border-line text-ink-soft' => $paymentMethod !== $method->value,
                        ])>
                            <input type="radio" wire:model.live="paymentMethod" value="{{ $method->value }}" class="sr-only">{{ $method->label() }}
                        </label>
                    @endforeach
                </div>

                @if ($paymentMethod === 'cash')
                    <label for="tendered" class="block text-sm font-semibold text-ink-soft">Entregado</label>
                    <input id="tendered" type="text" inputmode="decimal" wire:model.live.debounce.300ms="tendered"
                           class="mt-1.5 block h-14 w-full rounded-control border border-line-strong bg-card px-4 text-right text-2xl font-extrabold">
                    @error('tendered') <p class="mt-1 text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror
                    <div class="mt-2 grid grid-cols-5 gap-1.5 text-sm font-bold">
                        <button type="button" wire:click="exactAmount" class="min-h-11 rounded-control bg-sunken hover:bg-line">Exacto</button>
                        @foreach ([5, 10, 20, 50] as $note)
                            <button type="button" wire:click="addTendered({{ $note }})" class="min-h-11 rounded-control bg-sunken hover:bg-line">+{{ $note }} €</button>
                        @endforeach
                    </div>
                    @if (! is_null($this->change))
                        <p @class(['mt-4 flex items-baseline justify-between rounded-control px-4 py-3 text-xl font-extrabold', 'bg-success-soft text-success-ink' => $this->change >= 0, 'bg-red-50' => $this->change < 0, 'text-red-700' => $this->change < 0])>
                            <span>{{ $this->change >= 0 ? 'Cambio' : 'Falta' }}</span>
                            <span data-change>{{ Number::currency(abs($this->change), 'EUR', 'es') }}</span>
                        </p>
                    @endif
                @else
                    <p class="mb-3 text-[15px] text-ink-soft">Cobra en el datáfono y, si quieres, anota el código de autorización del recibo.</p>
                    <label for="authCode" class="block text-sm font-semibold text-ink-soft">Código de autorización (opcional)</label>
                    <input id="authCode" type="text" wire:model="authCode" maxlength="20" autocomplete="off"
                           class="mt-1.5 block h-12 w-full rounded-control border border-line-strong bg-card px-3 font-mono uppercase">
                    @error('authCode') <p class="mt-1 text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror
                @endif

                <button type="button" wire:click="confirmSale" wire:loading.attr="disabled"
                        class="mt-5 flex h-14 w-full items-center justify-center rounded-card bg-action text-lg font-extrabold text-on-action hover:bg-action-hover disabled:opacity-60">
                    Cobrar {{ Number::currency($this->totals->total, 'EUR', 'es') }}
                </button>
            </x-card>
        @endunless
    </aside>
</div>
