<x-layouts.app title="Inventario">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-stone-900">Inventario</h1>
            <p class="text-sm text-stone-500">Existencias reales repartidas entre la tienda física y el canal online.</p>
        </div>
        <div class="flex gap-2">
            @if ($lowCount > 0)
                <a href="{{ route('warehouse.stock.index', ['low' => 1]) }}" class="rounded-lg bg-amber-100 px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-200">
                    {{ $lowCount }} bajo mínimo
                </a>
            @endif
            @if (Route::has('warehouse.entries.create'))
                @can('stock.entry')
                    <a href="{{ route('warehouse.entries.create') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Registrar entrada</a>
                @endcan
            @endif
        </div>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o SKU"
               class="w-72 rounded-lg border border-stone-300 px-3 py-2 text-sm">
        <select name="category" class="rounded-lg border border-stone-300 px-3 py-2 text-sm">
            <option value="">Todas las categorías</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-stone-600">
            <input type="checkbox" name="low" value="1" class="rounded border-stone-300 text-emerald-600" @checked(request('low'))>
            Solo bajo mínimo
        </label>
        <x-button variant="secondary">Filtrar</x-button>
    </form>

    <div class="overflow-x-auto rounded-xl border border-stone-200 bg-white">
        <table class="min-w-full divide-y divide-stone-200 text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
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
            <tbody class="divide-y divide-stone-100">
                @forelse ($stocks as $stock)
                    @php($product = $stock->product)
                    <tr @class(['bg-amber-50' => $stock->is_below_minimum])>
                        <td class="px-4 py-3">
                            <p class="font-medium text-stone-900">{{ $product->name }}</p>
                            <p class="text-xs text-stone-500">
                                <span class="font-mono">{{ $product->sku }}</span> · {{ $product->category->name }}
                                @if ($product->location) · {{ $product->location }} @endif
                            </p>
                        </td>
                        <td class="px-4 py-3 text-right font-medium">{{ $product->formatQuantity($stock->on_hand) }}</td>
                        <td class="px-4 py-3 text-right">{{ $product->formatQuantity($stock->store_qty) }}</td>
                        <td class="px-4 py-3 text-right">{{ $product->formatQuantity($stock->online_qty) }}</td>
                        <td class="px-4 py-3 text-right text-stone-500">{{ $product->formatQuantity($stock->reserved_qty) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-emerald-700">{{ $product->formatQuantity($stock->available_online) }}</td>
                        <td class="px-4 py-3 text-right">
                            {{ $product->formatQuantity($stock->min_alert) }}
                            @if ($stock->is_below_minimum)
                                <span class="ml-1 rounded-full bg-amber-200 px-1.5 text-xs text-amber-900">Bajo mínimo</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-stone-500">No hay productos que coincidan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $stocks->links() }}</div>
</x-layouts.app>
