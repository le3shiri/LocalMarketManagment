<x-layouts.app title="Recuento de inventario">
    <x-warehouse-tabs />

    <div class="grid gap-6 lg:grid-cols-5">
        <x-card title="Ajuste tras recuento físico" class="lg:col-span-2">
            <p class="mb-4 text-sm text-ink-muted">Cuenta lo que hay realmente en la tienda y el almacén y escríbelo aquí. La diferencia se registra con su motivo.</p>
            <form method="POST" action="{{ route('warehouse.counts.store') }}" class="space-y-4">
                @csrf
                <div class="space-y-1">
                    <label for="product_id" class="block text-sm font-medium text-ink-soft">Producto</label>
                    <select id="product_id" name="product_id" required class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
                        <option value="">Elige un producto…</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(old('product_id', $selected) == $product->id)>
                                {{ $product->name }} · en sistema {{ $product->formatQuantity($product->stock->on_hand) }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <x-input name="counted" label="Cantidad contada (existencias reales)" inputmode="decimal" required />
                <x-input name="reason" label="Motivo" required placeholder="Ej.: recuento de cierre, rotura no registrada" />
                <x-button class="w-full">Registrar recuento</x-button>
            </form>
        </x-card>

        <x-card title="Últimos ajustes" class="lg:col-span-3">
            <ul class="divide-y divide-line text-sm">
                @forelse ($recent as $movement)
                    @php($delta = $movement->store_delta + $movement->online_delta)
                    <li class="flex justify-between gap-4 py-2">
                        <div>
                            <p class="font-medium">{{ $movement->product->name }}</p>
                            <p class="text-xs text-ink-muted">{{ $movement->reason }} · {{ $movement->user?->name }} · {{ $movement->created_at->diffForHumans() }}</p>
                        </div>
                        <p @class(['whitespace-nowrap font-medium', 'text-action' => $delta > 0, 'text-red-600' => $delta < 0])>
                            {{ $delta > 0 ? '+' : '' }}{{ $movement->product->formatQuantity($delta) }}
                        </p>
                    </li>
                @empty
                    <li class="py-6 text-center text-ink-muted">Todavía no hay ajustes.</li>
                @endforelse
            </ul>
        </x-card>
    </div>
</x-layouts.app>
