<div class="grid gap-6 lg:grid-cols-5">
    <div class="space-y-4 lg:col-span-3">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-stone-900">Caja</h1>
            <span class="text-sm text-stone-500">{{ auth()->user()->name }} · {{ now()->format('d/m/Y') }}</span>
        </div>

        <form wire:submit="scan" class="relative">
            <input type="search" wire:model.live.debounce.200ms="search" autofocus autocomplete="off"
                   x-data x-init="$el.focus()" @focus-search.window="$el.focus()"
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

        <div class="rounded-xl border border-stone-200 bg-white">
            <ul class="divide-y divide-stone-100">
                @forelse ($items as $id => $line)
                    <li class="flex items-center gap-3 px-4 py-3" wire:key="pos-line-{{ $id }}">
                        <span class="flex-1 font-medium">{{ $line['product']->name }}</span>
                        <span class="text-sm">{{ $line['product']->formatQuantity($line['quantity']) }}</span>
                    </li>
                @empty
                    <li class="px-4 py-10 text-center text-stone-400">Escanea o busca un producto para empezar la venta.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <aside class="lg:col-span-2">
        <x-card title="Venta en curso">
            <p class="text-sm text-stone-500">{{ $items->count() }} {{ Str::plural('línea', $items->count()) }}</p>
        </x-card>
    </aside>
</div>
