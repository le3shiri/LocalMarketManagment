<x-layouts.app title="Productos">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-[28px] font-extrabold tracking-tight text-ink">Productos</h1>
            <p class="mt-1 text-[15px] text-ink-muted">{{ $products->total() }} productos en el catálogo</p>
        </div>
        <a href="{{ route('admin.products.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-control bg-action px-5 text-[15px] font-bold text-on-action hover:bg-action-hover">Nuevo producto</a>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o SKU"
               class="w-72 min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
        <select name="category" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
            <option value="">Todas las categorías</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <x-button variant="secondary">Filtrar</x-button>
    </form>

    <div class="overflow-x-auto rounded-card bg-card shadow-card">
        <table class="min-w-full divide-y divide-line text-sm">
            <thead class="bg-sunken text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted">
                <tr>
                    <th scope="col" class="px-4 py-3">Producto</th>
                    <th scope="col" class="px-4 py-3">SKU</th>
                    <th scope="col" class="px-4 py-3">Categoría</th>
                    <th scope="col" class="px-4 py-3">Precio</th>
                    <th scope="col" class="px-4 py-3">Venta</th>
                    <th scope="col" class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($products as $product)
                    <tr @class(['opacity-60' => ! $product->is_active])>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if ($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="" class="h-10 w-10 rounded-control object-cover">
                                @else
                                    @php($c = \App\Support\SectionColors::for($product->category?->slug))
                                    <div class="grid h-10 w-10 place-items-center rounded-control text-lg font-light {{ $c['tint'] }} {{ $c['ink'] }}" aria-hidden="true">{{ mb_substr($product->name, 0, 1) }}</div>
                                @endif
                                <div>
                                    <p class="font-medium text-ink">{{ $product->name }}</p>
                                    <p class="text-xs text-ink-muted">
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
                                        'bg-success-soft text-success-ink hover:brightness-95' => $product->is_active,
                                        'bg-sunken text-ink-muted hover:bg-line' => ! $product->is_active,
                                    ]) title="Cambiar estado de venta">{{ $product->is_active ? 'A la venta' : 'Retirado' }}</button>
                                </form>
                            @else
                                <span class="text-xs">{{ $product->is_active ? 'A la venta' : 'Retirado' }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if (Route::has('admin.products.edit'))
                                <a href="{{ route('admin.products.edit', $product) }}" class="inline-flex min-h-11 items-center rounded-control px-3 font-bold text-action hover:bg-action-soft">Editar</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-ink-muted">No hay productos que coincidan con la búsqueda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
</x-layouts.app>
