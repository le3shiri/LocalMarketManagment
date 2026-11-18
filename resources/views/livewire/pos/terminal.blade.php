@use('Illuminate\Support\Number')
<div class="grid gap-6 lg:grid-cols-5">
    <div class="space-y-4 lg:col-span-3">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-stone-900">Caja</h1>
            <span class="text-sm text-stone-500">{{ auth()->user()->name }} · {{ now()->format('d/m/Y') }}</span>
        </div>

        <form wire:submit="scan" class="relative">
            <input type="search" wire:model.live.debounce.200ms="search" autofocus autocomplete="off"
                   x-data x-init="$el.focus()" @focus-search.window="$nextTick(() => $el.focus())"
                   placeholder="Escanea el código de barras o escribe el nombre / SKU y pulsa Enter"
                   class="block w-full rounded-xl border-2 border-emerald-500 px-4 py-3 text-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-300"
                   aria-label="Buscar producto o escanear código">
            @if ($this->results->isNotEmpty())
                <ul class="absolute z-10 mt-1 w-full divide-y divide-stone-100 rounded-xl border border-stone-200 bg-white shadow-lg">
                    @foreach ($this->results as $result)
                        <li wire:key="pos-result-{{ $result->id }}">
                            <button type="button" wire:click="add({{ $result->id }})" class="flex w-full items-center justify-between px-4 py-2 text-left hover:bg-emerald-50">
                                <span>{{ $result->name }} <span class="font-mono text-xs text-stone-400">{{ $result->sku }}</span></span>
                                <span class="text-sm text-stone-600">{{ $result->formatted_price }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </form>
        @if ($message)
            <p class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800" role="alert">{{ $message }}</p>
        @endif

        <div class="overflow-hidden rounded-xl border border-stone-200 bg-white">
            <table class="w-full text-sm">
                <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-4 py-2">Producto</th>
                        <th class="px-4 py-2 text-right">Precio</th>
                        <th class="px-4 py-2 text-center">Cantidad</th>
                        <th class="px-4 py-2 text-right">Importe</th>
                        <th class="w-8"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($this->totals->lines as $line)
                        @php($product = $line['product'])
                        <tr wire:key="pos-line-{{ $product->id }}">
                            <td class="px-4 py-2">
                                <span class="font-medium">{{ $product->name }}</span>
                                <span class="block text-xs text-stone-400">IVA {{ $product->vat_rate }} %</span>
                            </td>
                            <td class="px-4 py-2 text-right">{{ $product->formatted_price }}</td>
                            <td class="px-4 py-2 text-center">
                                <input type="number" value="{{ $line['quantity'] }}" min="0"
                                       step="{{ $product->unit_type === \App\Enums\UnitType::Weight ? '0.005' : '1' }}"
                                       wire:change="setQuantity({{ $product->id }}, $event.target.value)"
                                       class="w-20 rounded-lg border border-stone-300 px-2 py-1 text-right" aria-label="Cantidad de {{ $product->name }}">
                                <span class="text-xs text-stone-500">{{ $product->unit_type->suffix() }}</span>
                            </td>
                            <td class="px-4 py-2 text-right font-medium">{{ Number::currency($line['line_total'], 'EUR', 'es') }}</td>
                            <td class="pr-3 text-right">
                                <button type="button" wire:click="removeLine({{ $product->id }})" class="text-stone-400 hover:text-red-600" title="Quitar línea">✕</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-stone-400">Escanea o busca un producto para empezar la venta.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <aside class="space-y-4 lg:col-span-2">
        <x-card>
            <p class="text-sm uppercase tracking-wide text-stone-400">Total a cobrar</p>
            <p class="text-5xl font-bold text-stone-900" data-total>{{ Number::currency($this->totals->total, 'EUR', 'es') }}</p>

            @if (! $this->totals->isEmpty())
                <table class="mt-4 w-full text-xs text-stone-500">
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
                <button type="button" wire:click="clear" wire:confirm="¿Anular la venta en curso?" class="mt-4 text-sm text-stone-500 hover:text-red-600">Anular venta</button>
            @endif
        </x-card>
    </aside>
</div>
