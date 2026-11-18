<x-layouts.app title="Movimientos de stock">
    <x-warehouse-tabs />

    <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-stone-900">Movimientos de stock</h1>
            <p class="text-sm text-stone-500">Libro de movimientos: quién movió qué, cuándo y por qué.</p>
        </div>
        <a href="{{ route('warehouse.movements.export', request()->query()) }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:bg-stone-50">Exportar CSV</a>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <select name="product" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Producto">
            <option value="">Todos los productos</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected(($filters['product'] ?? null) == $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>
        <select name="type" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Tipo">
            <option value="">Todos los tipos</option>
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        <select name="user" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Responsable">
            <option value="">Cualquier responsable</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected(($filters['user'] ?? null) == $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Desde">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Hasta">
        <x-button variant="secondary">Filtrar</x-button>
    </form>
    @error('to') <p class="mb-3 text-xs text-red-600">{{ $message }}</p> @enderror

    <div class="overflow-x-auto rounded-xl border border-stone-200 bg-white">
        <table class="min-w-full divide-y divide-stone-200 text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                <tr>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Producto</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3 text-right">Tienda</th>
                    <th class="px-4 py-3 text-right">Online</th>
                    <th class="px-4 py-3 text-right">Reservado</th>
                    <th class="px-4 py-3 text-right">Existencias</th>
                    <th class="px-4 py-3">Referencia / motivo</th>
                    <th class="px-4 py-3">Responsable</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($movements as $m)
                    @php($fmt = fn ($v) => $v == 0 ? '—' : ($v > 0 ? '+' : '').$m->product?->formatQuantity($v))
                    <tr>
                        <td class="whitespace-nowrap px-4 py-2 text-stone-500">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2">{{ $m->product?->name ?? '—' }}</td>
                        <td class="px-4 py-2"><span class="whitespace-nowrap rounded-full bg-stone-100 px-2 py-0.5 text-xs">{{ $m->type->label() }}</span></td>
                        <td @class(['px-4 py-2 text-right', 'text-emerald-700' => $m->store_delta > 0, 'text-red-600' => $m->store_delta < 0])>{{ $fmt($m->store_delta) }}</td>
                        <td @class(['px-4 py-2 text-right', 'text-emerald-700' => $m->online_delta > 0, 'text-red-600' => $m->online_delta < 0])>{{ $fmt($m->online_delta) }}</td>
                        <td class="px-4 py-2 text-right text-stone-500">{{ $fmt($m->reserved_delta) }}</td>
                        <td class="px-4 py-2 text-right font-medium">{{ $m->product?->formatQuantity($m->on_hand_after) }}</td>
                        <td class="px-4 py-2 text-xs">
                            @if ($m->reference)<span class="font-mono">{{ $m->reference }}</span>@endif
                            @if ($m->reason)<span class="block text-stone-500">{{ $m->reason }}</span>@endif
                        </td>
                        <td class="px-4 py-2">{{ $m->user?->name ?? 'Sistema' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-stone-500">No hay movimientos con estos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $movements->links() }}</div>
</x-layouts.app>
