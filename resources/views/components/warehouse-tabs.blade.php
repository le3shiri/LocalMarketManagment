{{-- Sub-navigation of the warehouse module; each tab only shows when its route exists and the user may use it. --}}
@php($tabs = [
    ['warehouse.stock.index', 'Inventario', 'stock.view'],
    ['warehouse.movements.index', 'Movimientos', 'stock.view'],
    ['warehouse.replenishment.index', 'Reposición', 'stock.view'],
    ['warehouse.expiry.index', 'Caducidades', 'stock.view'],
    ['warehouse.entries.create', 'Entrada', 'stock.entry'],
    ['warehouse.exits.create', 'Salidas', 'stock.exit'],
    ['warehouse.write-offs.index', 'Mermas', 'stock.view'],
    ['warehouse.counts.create', 'Recuento', 'stock.adjust'],
    ['warehouse.transfers.create', 'Canales', 'stock.transfer'],
])

<nav class="mb-6 flex flex-wrap gap-1 border-b border-line text-sm" aria-label="Almacén">
    @foreach ($tabs as [$route, $label, $permission])
        @if (Route::has($route) && auth()->user()->can($permission))
            <a href="{{ route($route) }}" @class([
                '-mb-px border-b-2 px-3 py-2',
                'border-action font-medium text-action' => request()->routeIs(Str::beforeLast($route, '.').'.*'),
                'border-transparent text-ink-muted hover:text-ink' => ! request()->routeIs(Str::beforeLast($route, '.').'.*'),
            ])>{{ $label }}</a>
        @endif
    @endforeach
</nav>
