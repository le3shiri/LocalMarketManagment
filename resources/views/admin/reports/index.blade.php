@use('Illuminate\Support\Number')
<x-layouts.app title="Informe de ventas">
    <style>@media print { header, footer, .no-print { display: none !important; } main { padding: 0 !important; } }</style>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-[28px] font-extrabold tracking-tight text-ink">Informe de ventas</h1>
            <p class="text-sm text-ink-muted">Del {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }} · por {{ mb_strtolower($groups[$group]) }}</p>
        </div>
        <div class="no-print flex flex-wrap items-end gap-2">
            <form method="GET" class="flex flex-wrap items-end gap-2">
                <input type="date" name="from" value="{{ $from->toDateString() }}" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Desde">
                <input type="date" name="to" value="{{ $to->toDateString() }}" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Hasta">
                <select name="group" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Agrupar por">
                    @foreach ($groups as $value => $label)
                        <option value="{{ $value }}" @selected($group === $value)>Por {{ mb_strtolower($label) }}</option>
                    @endforeach
                </select>
                <x-button variant="secondary">Ver</x-button>
            </form>
            <a href="{{ route('admin.reports.top', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="rounded-control border border-line-strong bg-white px-4 py-2 text-sm font-medium hover:bg-page">Más vendidos</a>
            <a href="{{ route('admin.reports.export', request()->query()) }}" class="rounded-control border border-line-strong bg-white px-4 py-2 text-sm font-medium hover:bg-page">CSV</a>
            <button type="button" onclick="window.print()" class="inline-flex min-h-11 items-center justify-center rounded-control bg-action px-5 text-[15px] font-bold text-on-action hover:bg-action-hover">Imprimir</button>
        </div>
    </div>
    @error('to') <p class="no-print mb-3 text-xs text-red-600">{{ $message }}</p> @enderror

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        @foreach (['till' => 'Ventas en caja', 'orders' => 'Pedidos cobrados', 'refunds' => 'Devoluciones', 'net' => 'Neto'] as $key => $label)
            <div class="rounded-card border border-line bg-white p-4">
                <p class="text-xs uppercase tracking-wide text-ink-muted">{{ $label }}</p>
                <p @class(['mt-1 text-2xl font-bold', 'text-red-700' => $key === 'refunds', 'text-action' => $key === 'net']) data-total="{{ $key }}">
                    {{ $key === 'refunds' && $totals[$key] > 0 ? '−' : '' }}{{ Number::currency($totals[$key], 'EUR', 'es') }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="overflow-x-auto rounded-card border border-line bg-white lg:col-span-2">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="bg-sunken text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted">
                    <tr>
                        <th class="px-4 py-3">{{ $groups[$group] }}</th>
                        <th class="px-4 py-3 text-right">Tickets</th>
                        <th class="px-4 py-3 text-right">Caja</th>
                        <th class="px-4 py-3 text-right">Pedidos</th>
                        <th class="px-4 py-3 text-right">Devol.</th>
                        <th class="px-4 py-3 text-right">Neto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($rows as $row)
                        <tr @class(['text-ink-muted' => $row['net'] == 0])>
                            <td class="whitespace-nowrap px-4 py-2">{{ $row['label'] }}</td>
                            <td class="px-4 py-2 text-right">{{ $row['tickets'] }}</td>
                            <td class="px-4 py-2 text-right">{{ Number::currency($row['till'], 'EUR', 'es') }}</td>
                            <td class="px-4 py-2 text-right">{{ Number::currency($row['orders'], 'EUR', 'es') }}</td>
                            <td class="px-4 py-2 text-right">{{ $row['refunds'] > 0 ? '−'.Number::currency($row['refunds'], 'EUR', 'es') : '—' }}</td>
                            <td class="px-4 py-2 text-right font-medium">{{ Number::currency($row['net'], 'EUR', 'es') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-line font-semibold">
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
                <thead class="text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted"><tr><th>Tipo</th><th class="text-right">Base</th><th class="text-right">Cuota</th></tr></thead>
                <tbody class="divide-y divide-line">
                    @forelse ($vat as $rate => $row)
                        <tr data-vat="{{ $rate }}"><td class="py-1.5">{{ $rate }} %</td><td class="py-1.5 text-right">{{ Number::currency($row['base'], 'EUR', 'es') }}</td><td class="py-1.5 text-right">{{ Number::currency($row['vat'], 'EUR', 'es') }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-3 text-center text-ink-muted">Sin tickets en el periodo.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <p class="mt-3 text-xs text-ink-muted">Los pedidos se cobran al entregarlos con su propio justificante; su IVA se incorporará cuando haya facturación online (fase 3).</p>
        </x-card>
    </div>
</x-layouts.app>
