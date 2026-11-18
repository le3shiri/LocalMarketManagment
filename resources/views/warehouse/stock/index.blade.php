<x-layouts.app title="Inventario">
    <x-warehouse-tabs />

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-[28px] font-extrabold tracking-tight text-ink">Inventario</h1>
            <p class="text-sm text-ink-muted">Existencias reales repartidas entre la tienda física y el canal online.</p>
        </div>
        <div class="flex gap-2">
            @if ($lowCount > 0)
                <a href="{{ route('warehouse.stock.index', ['low' => 1]) }}" class="rounded-control bg-warn-soft px-3 py-2 text-sm font-medium text-stock-low hover:brightness-95">
                    {{ $lowCount }} bajo mínimo
                </a>
            @endif
            @if (Route::has('warehouse.entries.create'))
                @can('stock.entry')
                    <a href="{{ route('warehouse.entries.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-control bg-action px-5 text-[15px] font-bold text-on-action hover:bg-action-hover">Registrar entrada</a>
                @endcan
            @endif
        </div>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o SKU"
               class="w-72 min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
        <select name="category" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
            <option value="">Todas las categorías</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-ink-soft">
            <input type="checkbox" name="low" value="1" class="h-5 w-5 rounded border-line-strong accent-action" @checked(request('low'))>
            Solo bajo mínimo
        </label>
        <x-button variant="secondary">Filtrar</x-button>
    </form>

    <div class="overflow-x-auto rounded-card border border-line bg-white">
        <table class="min-w-full divide-y divide-line text-sm">
            <thead class="bg-sunken text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted">
                <tr>
                    <th class="px-4 py-3">Producto</th>
                    <th class="px-4 py-3 text-right">Existencias</th>
                    <th class="px-4 py-3 text-right">Tienda</th>
                    <th class="px-4 py-3 text-right">Online</th>
                    <th class="px-4 py-3 text-right">Reservado</th>
                    <th class="px-4 py-3 text-right">Disponible online</th>
                    <th class="px-4 py-3 text-right">Mínimo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($stocks as $stock)
                    @php($product = $stock->product)
                    <tr @class(['bg-warn-soft' => $stock->is_below_minimum])>
                        <td class="px-4 py-3">
                            <a href="{{ route('warehouse.batches.index', $product) }}" class="font-medium text-ink hover:text-action hover:underline" title="Ver lotes">{{ $product->name }}</a>
                            <p class="text-xs text-ink-muted">
                                <span class="font-mono">{{ $product->sku }}</span> · {{ $product->category->name }}
                                @if ($product->location) · {{ $product->location }} @endif
                            </p>
                        </td>
                        <td class="px-4 py-3 text-right font-medium">{{ $product->formatQuantity($stock->on_hand) }}</td>
                        <td class="px-4 py-3 text-right">{{ $product->formatQuantity($stock->store_qty) }}</td>
                        <td class="px-4 py-3 text-right">{{ $product->formatQuantity($stock->online_qty) }}</td>
                        <td class="px-4 py-3 text-right text-ink-muted">{{ $product->formatQuantity($stock->reserved_qty) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-action">{{ $product->formatQuantity($stock->available_online) }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('stock.entry')
                                <form method="POST" action="{{ route('warehouse.minimum.update', $product) }}" class="inline-flex items-center justify-end gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="min_alert" inputmode="decimal" value="{{ $stock->min_alert + 0 }}"
                                           class="w-16 rounded-control border border-line-strong px-2 py-1 text-right text-sm" aria-label="Mínimo de {{ $product->name }}">
                                    <span class="text-xs text-ink-muted">{{ $product->unit_type->suffix() }}</span>
                                    <button class="rounded-control px-1.5 py-1 text-xs text-action hover:bg-action-soft" title="Guardar mínimo">✓</button>
                                </form>
                            @else
                                {{ $product->formatQuantity($stock->min_alert) }}
                            @endcan
                            @if ($stock->is_below_minimum)
                                <span class="ml-1 rounded-full bg-warn-soft px-1.5 text-xs text-stock-low">Bajo mínimo</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-ink-muted">No hay productos que coincidan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $stocks->links() }}</div>
</x-layouts.app>
