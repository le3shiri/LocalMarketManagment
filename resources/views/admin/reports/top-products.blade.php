@use('Illuminate\Support\Number')
<x-layouts.app title="Productos más vendidos">
    <style>@media print { header, footer, .no-print { display: none !important; } }</style>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('admin.reports.index') }}" class="no-print text-sm text-stone-500 hover:underline">← Informe de ventas</a>
            <h1 class="text-2xl font-bold text-stone-900">Productos más vendidos</h1>
            <p class="text-sm text-stone-500">Del {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }} · {{ $channels[$filters['channel']] }} · tendencia frente al periodo anterior de la misma duración</p>
        </div>
        <div class="flex gap-2">
        <x-csv-link :href="route('admin.reports.top.export', request()->query())" />
        <button type="button" onclick="window.print()" class="no-print rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:bg-stone-50">Imprimir</button>
        </div>
    </div>

    <form method="GET" class="no-print mb-4 flex flex-wrap items-end gap-2">
        <input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Desde">
        <input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Hasta">
        <select name="channel" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Canal">
            @foreach ($channels as $value => $label)
                <option value="{{ $value }}" @selected($filters['channel'] === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="category" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Categoría">
            <option value="">Todas las categorías</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected($filters['category'] === $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select name="sort" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Ordenar por">
            <option value="revenue" @selected($filters['sort'] === 'revenue')>Por importe</option>
            <option value="quantity" @selected($filters['sort'] === 'quantity')>Por cantidad</option>
        </select>
        <select name="limit" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Número de productos">
            @foreach ([10, 20, 50] as $n)
                <option value="{{ $n }}" @selected($filters['limit'] === $n)>Top {{ $n }}</option>
            @endforeach
        </select>
        <x-button variant="secondary">Ver</x-button>
    </form>

    @php($maxRevenue = max(0.01, (float) $rows->max('revenue')))
    <div class="overflow-x-auto rounded-xl border border-stone-200 bg-white">
        <table class="min-w-full divide-y divide-stone-200 text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Producto</th>
                    <th class="px-4 py-3 text-right">Vendido</th>
                    <th class="px-4 py-3 text-right">Importe</th>
                    <th class="w-1/4 px-4 py-3"></th>
                    <th class="px-4 py-3 text-right">Tendencia</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($rows as $row)
                    <tr data-rank="{{ $row['rank'] }}">
                        <td class="px-4 py-3 font-semibold text-stone-400">{{ $row['rank'] }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $row['name'] }}</p>
                            <p class="text-xs text-stone-500">{{ $row['category'] ?? 'Producto retirado del catálogo' }}</p>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">{{ Number::format($row['quantity'], maxPrecision: 2, locale: 'es') }} {{ $row['unit'] }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-medium">{{ Number::currency($row['revenue'], 'EUR', 'es') }}</td>
                        <td class="px-4 py-3">
                            <div class="h-2 rounded-full bg-stone-100">
                                <div class="h-2 rounded-full bg-emerald-500" style="width: {{ round(100 * $row['revenue'] / $maxRevenue) }}%"></div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-xs" data-trend="{{ $row['trend'] ?? 'new' }}">
                            @if (is_null($row['trend']))
                                <span class="rounded-full bg-sky-50 px-2 py-0.5 font-medium text-sky-700">Nuevo</span>
                            @else
                                <span @class(['font-semibold', 'text-emerald-700' => $row['trend'] >= 0, 'text-red-600' => $row['trend'] < 0])>
                                    {{ $row['trend'] >= 0 ? '▲' : '▼' }} {{ Number::format(abs($row['trend']), maxPrecision: 1, locale: 'es') }} %
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-stone-500">No hay ventas en este periodo.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="mt-3 text-xs text-stone-500">Caja: tickets menos devoluciones. Pedidos: solo entregados, con la cantidad realmente entregada (peso real).</p>
</x-layouts.app>
