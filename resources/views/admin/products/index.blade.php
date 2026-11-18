<x-layouts.app title="Productos">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-stone-900">Productos</h1>
            <p class="text-sm text-stone-500">{{ $products->total() }} productos en el catálogo</p>
        </div>
        <a href="{{ route('admin.products.create') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Nuevo producto</a>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o SKU"
               class="w-72 rounded-lg border border-stone-300 px-3 py-2 text-sm">
        <select name="category" class="rounded-lg border border-stone-300 px-3 py-2 text-sm">
            <option value="">Todas las categorías</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <x-button variant="secondary">Filtrar</x-button>
    </form>

    <div class="overflow-x-auto rounded-xl border border-stone-200 bg-white">
        <table class="min-w-full divide-y divide-stone-200 text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                <tr>
                    <th class="px-4 py-3">Producto</th>
                    <th class="px-4 py-3">SKU</th>
                    <th class="px-4 py-3">Categoría</th>
                    <th class="px-4 py-3">Precio</th>
                    <th class="px-4 py-3">Venta</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($products as $product)
                    <tr @class(['opacity-60' => ! $product->is_active])>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if ($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="" class="h-10 w-10 rounded-lg object-cover">
                                @else
                                    <div class="grid h-10 w-10 place-items-center rounded-lg bg-stone-100 text-xs text-stone-400">—</div>
                                @endif
                                <div>
                                    <p class="font-medium text-stone-900">{{ $product->name }}</p>
                                    <p class="text-xs text-stone-500">
                                        {{ $product->unit_type->label() }}@if ($product->is_perishable) · Perecedero @endif
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $product->sku }}</td>
                        <td class="px-4 py-3">{{ $product->category->name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $product->formatted_price }}</td>
                        <td class="px-4 py-3">
                            @if (Route::has('admin.products.toggle'))
                                <form method="POST" action="{{ route('admin.products.toggle', $product) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button @class([
                                        'rounded-full px-2 py-0.5 text-xs font-medium',
                                        'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' => $product->is_active,
                                        'bg-stone-100 text-stone-500 hover:bg-stone-200' => ! $product->is_active,
                                    ]) title="Cambiar estado de venta">{{ $product->is_active ? 'A la venta' : 'Retirado' }}</button>
                                </form>
                            @else
                                <span class="text-xs">{{ $product->is_active ? 'A la venta' : 'Retirado' }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if (Route::has('admin.products.edit'))
                                <a href="{{ route('admin.products.edit', $product) }}" class="rounded-md px-2 py-1 text-emerald-700 hover:bg-emerald-50">Editar</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-stone-500">No hay productos que coincidan con la búsqueda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
</x-layouts.app>
