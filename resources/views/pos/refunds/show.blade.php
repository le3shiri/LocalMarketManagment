@use('Illuminate\Support\Number')
<x-layouts.app :title="'Devolución '.$refund->code">
    <style>@media print { header, footer, .no-print { display: none !important; } }</style>

    <div class="no-print mb-4 flex justify-end gap-2">
        <button type="button" onclick="window.print()" class="rounded-control border border-line-strong bg-white px-4 py-2 text-sm font-medium hover:bg-page">Imprimir</button>
        <a href="{{ route('pos.tickets.show', $refund->sale) }}" class="inline-flex min-h-11 items-center justify-center rounded-control bg-action px-5 text-[15px] font-bold text-on-action hover:bg-action-hover">Volver al ticket</a>
    </div>

    <article class="mx-auto max-w-sm rounded-card border border-line bg-white p-6 font-mono text-xs shadow-card">
        <header class="mb-3 text-center">
            <p class="text-base font-bold">LOCALMARKET</p>
            <p class="mt-2 font-semibold">TICKET DE DEVOLUCIÓN</p>
            <p>Nº {{ $refund->code }} · {{ $refund->created_at->format('d/m/Y H:i') }}</p>
            <p>Ticket original: {{ $refund->sale->code }}</p>
            <p>Atendido por: {{ $refund->user?->name ?? '—' }}</p>
        </header>
        <table class="w-full border-y border-dashed border-line-strong">
            @foreach ($refund->items as $item)
                <tr>
                    <td class="py-1">
                        {{ $item->saleItem->product_name }}
                        <span class="block text-ink-muted">{{ Number::format($item->quantity, maxPrecision: 3, locale: 'es') }} {{ $item->saleItem->unit_type->suffix() }}{{ $item->restocked ? '' : ' · no vuelve a la venta' }}</span>
                    </td>
                    <td class="py-1 text-right">-{{ Number::currency((float) $item->amount, 'EUR', 'es') }}</td>
                </tr>
            @endforeach
        </table>
        <p class="mt-2 flex justify-between text-sm font-bold"><span>REEMBOLSO</span><span data-refund-total>-{{ Number::currency((float) $refund->amount, 'EUR', 'es') }}</span></p>
        <p class="flex justify-between"><span>Método</span><span>{{ $refund->payment?->method->label() }}</span></p>
        <p class="mt-3">Motivo: {{ $refund->reason }}</p>
    </article>
</x-layouts.app>
