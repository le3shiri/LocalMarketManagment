<x-layouts.app title="Stock por canal">
    <x-warehouse-tabs />

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Transferir entre tienda y online">
            <p class="mb-4 text-sm text-ink-muted">Por ejemplo a media mañana, si el stock online se agota y en la tienda sobra. Lo reservado para pedidos no se puede mover.</p>
            <form method="POST" action="{{ route('warehouse.transfers.store') }}" class="space-y-4">
                @csrf
                <div class="space-y-1">
                    <label for="product_id" class="block text-sm font-semibold text-ink-soft">Producto</label>
                    <select id="product_id" name="product_id" required class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
                        <option value="">Elige un producto…</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(old('product_id', $selected) == $product->id)>
                                {{ $product->name }} · tienda {{ $product->formatQuantity($product->stock->store_qty) }} · online {{ $product->formatQuantity($product->stock->online_qty) }} (reservado {{ $product->formatQuantity($product->stock->reserved_qty) }})
                            </option>
                        @endforeach
                    </select>
                    @error('product_id') <p class="text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror
                </div>
                <fieldset class="flex gap-4 text-sm">
                    <legend class="mb-1 block text-sm font-medium text-ink-soft">Dirección</legend>
                    <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 pr-3 font-semibold"><input type="radio" name="to" value="online" @checked(old('to', 'online') === 'online') class="accent-action"> Tienda → online</label>
                    <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 pr-3 font-semibold"><input type="radio" name="to" value="store" @checked(old('to') === 'store') class="accent-action"> Online → tienda</label>
                </fieldset>
                <x-input name="quantity" label="Cantidad" inputmode="decimal" required />
                <x-input name="reason" label="Motivo" required placeholder="Ej.: online agotado a media mañana" />
                <x-button class="w-full">Transferir</x-button>
            </form>
        </x-card>

        <div class="space-y-6">
            @can('stock.channels')
                <x-card title="Reparto por defecto">
                    <form method="POST" action="{{ route('warehouse.channels.shares') }}" class="grid grid-cols-2 gap-4">
                        @csrf
                        @method('PUT')
                        <x-input name="online_share" label="% online general" type="number" min="0" max="100" :value="$share" required />
                        <x-input name="online_share_fresh" label="% online frescos" type="number" min="0" max="100" :value="$shareFresh" required />
                        <p class="col-span-2 text-xs text-ink-muted">La propuesta recomienda empezar entre el 20 % y el 30 %, con menos en frescos, y revisarlo cada semana con las ventas reales.</p>
                        <x-button variant="secondary" class="col-span-2">Guardar porcentajes</x-button>
                    </form>
                </x-card>

                <x-card title="Aplicar el reparto">
                    <form method="POST" action="{{ route('warehouse.channels.rebalance') }}" class="space-y-3"
                          onsubmit="return confirm('¿Aplicar el reparto? Se registrará una transferencia por cada producto que cambie.')">
                        @csrf
                        <select name="category_id" class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Categoría">
                            <option value="">Todo el catálogo</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <x-button class="w-full">Aplicar {{ $share }} % / {{ $shareFresh }} % frescos</x-button>
                    </form>
                </x-card>
            @endcan

            <x-card title="Últimas transferencias">
                <ul class="divide-y divide-line text-sm">
                    @forelse ($recent as $movement)
                        <li class="flex justify-between gap-4 py-2">
                            <div>
                                <p class="font-medium">{{ $movement->product->name }}</p>
                                <p class="text-xs text-ink-muted">{{ $movement->reason }} · {{ $movement->user?->name }} · {{ $movement->created_at->diffForHumans() }}</p>
                            </div>
                            <p class="whitespace-nowrap text-xs">{{ $movement->online_delta > 0 ? 'Tienda → online' : 'Online → tienda' }}<br>{{ $movement->product->formatQuantity(abs($movement->online_delta)) }}</p>
                        </li>
                    @empty
                        <li class="py-6 text-center text-ink-muted">Todavía no hay transferencias.</li>
                    @endforelse
                </ul>
            </x-card>
        </div>
    </div>
</x-layouts.app>
