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

<nav class="mb-6 -mx-1 flex gap-1 overflow-x-auto rounded-control bg-sunken p-1 sm:flex-wrap" aria-label="Almacén">
    @foreach ($tabs as [$route, $label, $permission])
        @if (Route::has($route) && auth()->user()->can($permission))
            @php($current = request()->routeIs(Str::beforeLast($route, '.').'.*'))
            <a href="{{ route($route) }}" @if ($current) aria-current="page" @endif @class([
                'inline-flex min-h-11 flex-none items-center rounded-chip px-4 text-sm',
                'bg-card font-extrabold text-ink shadow-selected' => $current,
                'font-semibold text-ink-soft hover:bg-card/60 hover:text-ink' => ! $current,
            ])>{{ $label }}</a>
        @endif
    @endforeach
</nav>
