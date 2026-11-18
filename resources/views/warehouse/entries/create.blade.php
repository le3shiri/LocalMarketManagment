<x-layouts.app title="Entrada de mercancía">
    <a href="{{ route('warehouse.stock.index') }}" class="text-sm text-stone-500 hover:underline">← Volver al inventario</a>

    <div class="mt-3 grid gap-6 lg:grid-cols-5">
        <x-card title="Registrar entrada de mercancía" class="lg:col-span-2">
            <form method="POST" action="{{ route('warehouse.entries.store') }}" class="space-y-4">
                @csrf
                <div class="space-y-1">
                    <label for="product_id" class="block text-sm font-medium text-stone-700">Producto</label>
                    <select id="product_id" name="product_id" required class="block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm">
                        <option value="">Elige un producto…</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(old('product_id', $selected) == $product->id)>
                                {{ $product->name }} ({{ $product->sku }}) · {{ $product->unit_type->suffix() }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <x-input name="store_qty" label="Para tienda" inputmode="decimal" value="0" required />
                    <x-input name="online_qty" label="Para online" inputmode="decimal" value="0" required />
                </div>
                <p class="text-xs text-stone-500">Propuesta inicial: entre el 20 % y el 30 % para el canal online, menos en frescos.</p>

                <x-input name="reference" label="Albarán / referencia del proveedor" />
                <x-input name="reason" label="Observaciones" />

                <x-button class="w-full">Registrar entrada</x-button>
            </form>
        </x-card>

        <x-card title="Últimos movimientos" class="lg:col-span-3">
            <ul class="divide-y divide-stone-100 text-sm">
                @forelse ($recent as $movement)
                    <li class="flex items-start justify-between gap-4 py-2">
                        <div>
                            <p class="font-medium text-stone-900">{{ $movement->product->name }}</p>
                            <p class="text-xs text-stone-500">
                                {{ $movement->type->label() }} · {{ $movement->user?->name ?? 'Sistema' }} · {{ $movement->created_at->diffForHumans() }}
                                @if ($movement->reference) · {{ $movement->reference }} @endif
                            </p>
                        </div>
                        <p class="whitespace-nowrap text-right text-xs">
                            Tienda {{ $movement->store_delta >= 0 ? '+' : '' }}{{ $movement->product->formatQuantity($movement->store_delta) }}<br>
                            Online {{ $movement->online_delta >= 0 ? '+' : '' }}{{ $movement->product->formatQuantity($movement->online_delta) }}
                        </p>
                    </li>
                @empty
                    <li class="py-6 text-center text-stone-500">Todavía no hay movimientos.</li>
                @endforelse
            </ul>
        </x-card>
    </div>
</x-layouts.app>
