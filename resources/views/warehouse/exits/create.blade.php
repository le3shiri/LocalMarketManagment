<x-layouts.app title="Salida de mercancía">
    <x-warehouse-tabs />

    <div class="grid gap-6 lg:grid-cols-5">
        <x-card title="Registrar salida de mercancía" class="lg:col-span-2">
            <form method="POST" action="{{ route('warehouse.exits.store') }}" class="space-y-4">
                @csrf
                <div class="space-y-1">
                    <label for="product_id" class="block text-sm font-medium text-stone-700">Producto</label>
                    <select id="product_id" name="product_id" required class="block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm">
                        <option value="">Elige un producto…</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(old('product_id', $selected) == $product->id)>
                                {{ $product->name }} · tienda {{ $product->formatQuantity($product->stock->store_qty) }} · online libre {{ $product->formatQuantity($product->stock->available_online) }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <fieldset class="flex gap-4 text-sm">
                    <legend class="mb-1 block text-sm font-medium text-stone-700">Sale de</legend>
                    <label class="flex items-center gap-1"><input type="radio" name="channel" value="store" @checked(old('channel', 'store') === 'store') class="text-emerald-600"> Tienda</label>
                    <label class="flex items-center gap-1"><input type="radio" name="channel" value="online" @checked(old('channel') === 'online') class="text-emerald-600"> Stock online (sin reservar)</label>
                </fieldset>

                <x-input name="quantity" label="Cantidad" inputmode="decimal" required />

                <div class="space-y-1">
                    <label for="reason" class="block text-sm font-medium text-stone-700">Motivo</label>
                    <select id="reason" name="reason" required class="block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm">
                        @foreach ($reasons as $reason)
                            <option value="{{ $reason->value }}" @selected(old('reason') === $reason->value)>{{ $reason->label() }}</option>
                        @endforeach
                    </select>
                    @error('reason') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <x-input name="notes" label="Detalle (obligatorio si el motivo es «Otro»)" />
                <x-input name="reference" label="Referencia (albarán de devolución, etc.)" />

                <x-button class="w-full">Registrar salida</x-button>
            </form>
        </x-card>

        <x-card title="Últimas salidas" class="lg:col-span-3">
            <ul class="divide-y divide-stone-100 text-sm">
                @forelse ($recent as $movement)
                    <li class="flex justify-between gap-4 py-2">
                        <div>
                            <p class="font-medium">{{ $movement->product->name }}</p>
                            <p class="text-xs text-stone-500">{{ $movement->reason }} · {{ $movement->user?->name }} · {{ $movement->created_at->diffForHumans() }}</p>
                        </div>
                        <p class="whitespace-nowrap text-red-600">{{ $movement->product->formatQuantity($movement->store_delta + $movement->online_delta) }}</p>
                    </li>
                @empty
                    <li class="py-6 text-center text-stone-500">Todavía no hay salidas.</li>
                @endforelse
            </ul>
        </x-card>
    </div>
</x-layouts.app>
