@use('Illuminate\Support\Number')
<x-layouts.app title="Informe de ventas">
    <style>@media print { header, footer, .no-print { display: none !important; } main { padding: 0 !important; } }</style>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-stone-900">Informe de ventas</h1>
            <p class="text-sm text-stone-500">Del {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }} · por {{ mb_strtolower($groups[$group]) }}</p>
        </div>
        <div class="no-print flex flex-wrap items-end gap-2">
            <form method="GET" class="flex flex-wrap items-end gap-2">
                <input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Desde">
                <input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Hasta">
                <select name="group" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Agrupar por">
                    @foreach ($groups as $value => $label)
                        <option value="{{ $value }}" @selected($group === $value)>Por {{ mb_strtolower($label) }}</option>
                    @endforeach
                </select>
                <x-button variant="secondary">Ver</x-button>
            </form>
            <a href="{{ route('admin.reports.top', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:bg-stone-50">Más vendidos</a>
            <a href="{{ route('admin.reports.export', request()->query()) }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:bg-stone-50">CSV</a>
            <button type="button" onclick="window.print()" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Imprimir</button>
        </div>
    </div>
    @error('to') <p class="no-print mb-3 text-xs text-red-600">{{ $message }}</p> @enderror

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        @foreach (['till' => 'Ventas en caja', 'orders' => 'Pedidos cobrados', 'refunds' => 'Devoluciones', 'net' => 'Neto'] as $key => $label)
            <div class="rounded-xl border border-stone-200 bg-white p-4">
                <p class="text-xs uppercase tracking-wide text-stone-400">{{ $label }}</p>
                <p @class(['mt-1 text-2xl font-bold', 'text-red-700' => $key === 'refunds', 'text-emerald-700' => $key === 'net']) data-total="{{ $key }}">
                    {{ $key === 'refunds' && $totals[$key] > 0 ? '−' : '' }}{{ Number::currency($totals[$key], 'EUR', 'es') }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="overflow-x-auto rounded-xl border border-stone-200 bg-white lg:col-span-2">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-4 py-3">{{ $groups[$group] }}</th>
                        <th class="px-4 py-3 text-right">Tickets</th>
                        <th class="px-4 py-3 text-right">Caja</th>
                        <th class="px-4 py-3 text-right">Pedidos</th>
                        <th class="px-4 py-3 text-right">Devol.</th>
                        <th class="px-4 py-3 text-right">Neto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($rows as $row)
                        <tr @class(['text-stone-400' => $row['net'] == 0])>
                            <td class="whitespace-nowrap px-4 py-2">{{ $row['label'] }}</td>
                            <td class="px-4 py-2 text-right">{{ $row['tickets'] }}</td>
                            <td class="px-4 py-2 text-right">{{ Number::currency($row['till'], 'EUR', 'es') }}</td>
                            <td class="px-4 py-2 text-right">{{ Number::currency($row['orders'], 'EUR', 'es') }}</td>
                            <td class="px-4 py-2 text-right">{{ $row['refunds'] > 0 ? '−'.Number::currency($row['refunds'], 'EUR', 'es') : '—' }}</td>
                            <td class="px-4 py-2 text-right font-medium">{{ Number::currency($row['net'], 'EUR', 'es') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-stone-200 font-semibold">
                    <tr>
                        <td class="px-4 py-2">Total</td>
                        <td class="px-4 py-2 text-right">{{ $totals['tickets'] }}</td>
                        <td class="px-4 py-2 text-right">{{ Number::currency($totals['till'], 'EUR', 'es') }}</td>
                        <td class="px-4 py-2 text-right">{{ Number::currency($totals['orders'], 'EUR', 'es') }}</td>
                        <td class="px-4 py-2 text-right">−{{ Number::currency($totals['refunds'], 'EUR', 'es') }}</td>
                        <td class="px-4 py-2 text-right">{{ Number::currency($totals['net'], 'EUR', 'es') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <x-card title="IVA repercutido (caja)" class="h-fit">
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase text-stone-400"><tr><th>Tipo</th><th class="text-right">Base</th><th class="text-right">Cuota</th></tr></thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($vat as $rate => $row)
                        <tr data-vat="{{ $rate }}"><td class="py-1.5">{{ $rate }} %</td><td class="py-1.5 text-right">{{ Number::currency($row['base'], 'EUR', 'es') }}</td><td class="py-1.5 text-right">{{ Number::currency($row['vat'], 'EUR', 'es') }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-3 text-center text-stone-500">Sin tickets en el periodo.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <p class="mt-3 text-xs text-stone-500">Los pedidos se cobran al entregarlos con su propio justificante; su IVA se incorporará cuando haya facturación online (fase 3).</p>
        </x-card>
    </div>
</x-layouts.app>
