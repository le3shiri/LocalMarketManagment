@use('Illuminate\Support\Number')
<x-layouts.app :title="'Ticket '.$sale->code">
    <style>
        @media print {
            header, footer, .no-print { display: none !important; }
            main { padding: 0 !important; max-width: none !important; }
            .ticket { box-shadow: none !important; border: 0 !important; width: 80mm; margin: 0; }
        }
    </style>

    <div class="no-print mb-4 flex flex-wrap items-center justify-between gap-3">
        @if ($justSold)
            <p class="rounded-control bg-success-soft px-3 py-2 text-sm font-bold text-success-ink">
                Venta registrada.
                @if ($sale->payment?->change > 0)
                    Cambio: {{ Number::currency((float) $sale->payment->change, 'EUR', 'es') }}
                @endif
            </p>
        @else
            <span></span>
        @endif
        <div class="flex gap-2">
            <button type="button" onclick="window.print()" class="rounded-control border border-line-strong bg-card px-4 py-2 text-sm font-medium hover:bg-page">Imprimir ticket</button>
            @can('refunds.manage')
                <a href="{{ route('pos.refunds.create', $sale) }}" class="rounded-control border border-red-200 bg-card px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">Devolución</a>
            @endcan
            @can('pos.use')
                <a href="{{ route('pos.terminal') }}" class="inline-flex min-h-11 items-center justify-center rounded-control bg-action px-5 text-[15px] font-bold text-on-action hover:bg-action-hover">Nueva venta</a>
            @endcan
        </div>
    </div>

    <article class="ticket mx-auto max-w-sm rounded-card border border-line bg-card p-6 font-mono text-xs text-ink shadow-card">
        <header class="mb-3 text-center">
            <p class="text-base font-bold">LOCALMARKET</p>
            <p>Comercio de alimentación · Barcelona</p>
            <p class="mt-2 font-semibold">FACTURA SIMPLIFICADA</p>
            <p>Nº {{ $sale->code }} · {{ $sale->created_at->format('d/m/Y H:i') }}</p>
            <p>Le atendió: {{ $sale->cashier?->name ?? '—' }}</p>
        </header>

        <table class="w-full border-y border-dashed border-line-strong">
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td colspan="2" class="pt-1">{{ $item->product_name }}</td>
                    </tr>
                    <tr>
                        <td class="pb-1 pl-2 text-ink-muted">
                            {{ Number::format($item->quantity, maxPrecision: 3, locale: 'es') }} {{ $item->unit_type->suffix() }}
                            × {{ Number::currency((float) $item->unit_price, 'EUR', 'es') }} · IVA {{ $item->vat_rate }}%
                        </td>
                        <td class="pb-1 text-right">{{ Number::currency((float) $item->line_total, 'EUR', 'es') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="mt-2 flex justify-between text-sm font-bold"><span>TOTAL</span><span data-ticket-total>{{ Number::currency((float) $sale->total, 'EUR', 'es') }}</span></p>

        @if ($sale->payment)
            <p class="flex justify-between"><span>{{ $sale->payment->method->label() }}</span><span>{{ Number::currency((float) ($sale->payment->tendered ?? $sale->payment->amount), 'EUR', 'es') }}</span></p>
            @if (! is_null($sale->payment->change))
                <p class="flex justify-between"><span>Cambio</span><span>{{ Number::currency((float) $sale->payment->change, 'EUR', 'es') }}</span></p>
            @endif
            @if ($sale->payment->reference)
                <p class="flex justify-between"><span>Autorización</span><span>{{ $sale->payment->reference }}</span></p>
            @endif
        @endif

        <table class="mt-3 w-full border-t border-dashed border-line-strong pt-2">
            <thead><tr><th class="text-left font-normal">IVA</th><th class="text-right font-normal">Base</th><th class="text-right font-normal">Cuota</th></tr></thead>
            <tbody>
                @foreach ($sale->vat_breakdown as $rate => $row)
                    <tr>
                        <td>{{ $rate }} %</td>
                        <td class="text-right">{{ Number::currency($row['base'], 'EUR', 'es') }}</td>
                        <td class="text-right">{{ Number::currency($row['vat'], 'EUR', 'es') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="mt-4 text-center">¡Gracias por su compra!</p>
    </article>

    @if ($sale->refunds->isNotEmpty())
        <div class="no-print mx-auto mt-4 max-w-sm rounded-card border border-red-200 bg-red-50 p-4 text-sm">
            <p class="mb-2 font-medium text-red-800">Devoluciones de este ticket</p>
            <ul class="space-y-1">
                @foreach ($sale->refunds as $refund)
                    <li class="flex justify-between">
                        @can('refunds.manage')
                            <a href="{{ route('pos.refunds.show', $refund) }}" class="font-mono text-red-700 underline">{{ $refund->code }}</a>
                        @else
                            <span class="font-mono">{{ $refund->code }}</span>
                        @endcan
                        <span>-{{ Number::currency((float) $refund->amount, 'EUR', 'es') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</x-layouts.app>
