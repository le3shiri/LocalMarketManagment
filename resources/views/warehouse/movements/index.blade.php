<x-layouts.app title="Movimientos de stock">
    <x-warehouse-tabs />

    <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-[28px] font-extrabold tracking-tight text-ink">Movimientos de stock</h1>
            <p class="mt-1 text-[15px] text-ink-muted">Libro de movimientos: quién movió qué, cuándo y por qué.</p>
        </div>
        <a href="{{ route('warehouse.movements.export', request()->query()) }}" class="rounded-control border border-line-strong bg-card px-4 py-2 text-sm font-medium hover:bg-page">Exportar CSV</a>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <select name="product" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Producto">
            <option value="">Todos los productos</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected(($filters['product'] ?? null) == $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>
        <select name="type" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Tipo">
            <option value="">Todos los tipos</option>
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        <select name="user" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Responsable">
            <option value="">Cualquier responsable</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected(($filters['user'] ?? null) == $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Desde">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Hasta">
        <x-button variant="secondary">Filtrar</x-button>
    </form>
    @error('to') <p class="mb-3 text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror

    <div class="overflow-x-auto rounded-card bg-card shadow-card">
        <table class="min-w-full divide-y divide-line text-sm">
            <thead class="bg-sunken text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted">
                <tr>
                    <th scope="col" class="px-4 py-3">Fecha</th>
                    <th scope="col" class="px-4 py-3">Producto</th>
                    <th scope="col" class="px-4 py-3">Tipo</th>
                    <th scope="col" class="px-4 py-3 text-right">Tienda</th>
                    <th scope="col" class="px-4 py-3 text-right">Online</th>
                    <th scope="col" class="px-4 py-3 text-right">Reservado</th>
                    <th scope="col" class="px-4 py-3 text-right">Existencias</th>
                    <th scope="col" class="px-4 py-3">Referencia / motivo</th>
                    <th scope="col" class="px-4 py-3">Responsable</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($movements as $m)
                    @php($fmt = fn ($v) => $v == 0 ? '—' : ($v > 0 ? '+' : '').$m->product?->formatQuantity($v))
                    <tr>
                        <td class="whitespace-nowrap px-4 py-2 text-ink-muted">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2">{{ $m->product?->name ?? '—' }}</td>
                        <td class="px-4 py-2"><span class="whitespace-nowrap rounded-full bg-sunken px-2 py-0.5 text-xs">{{ $m->type->label() }}</span></td>
                        <td @class(['px-4 py-2 text-right', 'font-bold text-success-ink' => $m->store_delta > 0, 'text-red-700' => $m->store_delta < 0])>{{ $fmt($m->store_delta) }}</td>
                        <td @class(['px-4 py-2 text-right', 'font-bold text-success-ink' => $m->online_delta > 0, 'text-red-700' => $m->online_delta < 0])>{{ $fmt($m->online_delta) }}</td>
                        <td class="px-4 py-2 text-right text-ink-muted">{{ $fmt($m->reserved_delta) }}</td>
                        <td class="px-4 py-2 text-right font-medium">{{ $m->product?->formatQuantity($m->on_hand_after) }}</td>
                        <td class="px-4 py-2 text-xs">
                            @if ($m->reference)<span class="font-mono">{{ $m->reference }}</span>@endif
                            @if ($m->reason)<span class="block text-ink-muted">{{ $m->reason }}</span>@endif
                        </td>
                        <td class="px-4 py-2">{{ $m->user?->name ?? 'Sistema' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-ink-muted">No hay movimientos con estos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $movements->links() }}</div>
</x-layouts.app>
