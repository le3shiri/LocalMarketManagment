@use('Illuminate\Support\Number')
@use('App\Enums\PaymentMethod')
<x-layouts.app title="Ventas y pagos">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-[28px] font-extrabold tracking-tight text-ink">Ventas y pagos</h1>
            <p class="mt-1 text-[15px] text-ink-muted">Del {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}</p>
        </div>
        <form method="GET" class="flex flex-wrap items-end gap-2">
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Desde">
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Hasta">
            <select name="cashier" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Empleado">
                <option value="">Todo el personal</option>
                @foreach ($cashiers as $cashier)
                    <option value="{{ $cashier->id }}" @selected(($filters['cashier'] ?? null) == $cashier->id)>{{ $cashier->name }}</option>
                @endforeach
            </select>
            <select name="method" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Método">
                <option value="">Todos los métodos</option>
                @foreach (PaymentMethod::cases() as $method)
                    <option value="{{ $method->value }}" @selected(($filters['method'] ?? '') === $method->value)>{{ $method->label() }}</option>
                @endforeach
            </select>
            <x-button variant="secondary">Filtrar</x-button>
        </form>
        <x-csv-link :href="route('admin.sales.export', request()->query())" />
    </div>
    @error('to') <p class="mb-3 text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <div class="rounded-card bg-card p-5 shadow-card">
            <p class="text-[13px] font-bold uppercase tracking-[0.06em] text-ink-muted">Devoluciones</p>
            <p class="mt-1 text-2xl font-bold text-red-700" data-refunds-total>-{{ Number::currency($refundsTotal, 'EUR', 'es') }}</p>
        </div>
        <div class="rounded-card bg-card p-5 shadow-card">
            <p class="text-[13px] font-bold uppercase tracking-[0.06em] text-ink-muted">Ventas en caja</p>
            <p class="mt-1 text-2xl font-extrabold tracking-tight" data-sales-total>{{ Number::currency($salesTotal, 'EUR', 'es') }}</p>
        </div>
        <div class="rounded-card bg-card p-5 shadow-card">
            <p class="text-[13px] font-bold uppercase tracking-[0.06em] text-ink-muted">Tickets</p>
            <p class="mt-1 text-2xl font-extrabold tracking-tight" data-ticket-count>{{ $ticketCount }}</p>
        </div>
        <div class="rounded-card bg-card p-5 shadow-card">
            <p class="text-[13px] font-bold uppercase tracking-[0.06em] text-ink-muted">Ticket medio</p>
            <p class="mt-1 text-2xl font-extrabold tracking-tight" data-average>{{ Number::currency($averageTicket, 'EUR', 'es') }}</p>
        </div>
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        <x-card title="Cobros por método">
            <table class="w-full text-sm">
                <thead class="text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted">
                    <tr><th class="py-1">Método</th><th class="py-1">Origen</th><th class="py-1 text-right">Operaciones</th><th class="py-1 text-right">Importe</th></tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($byMethod as $method => $rows)
                        @foreach ($rows as $row)
                            <tr>
                                <td class="py-1.5">{{ $loop->first ? PaymentMethod::from($method)->label() : '' }}</td>
                                <td class="py-1.5 text-ink-muted">{{ $sourceLabels[$row->payable_type] ?? 'Otros' }}</td>
                                <td class="py-1.5 text-right">{{ $row->operations }}</td>
                                <td class="py-1.5 text-right">{{ Number::currency((float) $row->amount, 'EUR', 'es') }}</td>
                            </tr>
                        @endforeach
                        <tr class="font-semibold" data-method-total="{{ $method }}">
                            <td colspan="3" class="py-1.5 text-right">Total {{ PaymentMethod::from($method)->label() }}</td>
                            <td class="py-1.5 text-right">{{ Number::currency((float) $rows->sum('amount'), 'EUR', 'es') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-center text-ink-muted">Sin cobros en este periodo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        <x-card title="Ventas en caja por día">
            <table class="w-full text-sm">
                <thead class="text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted">
                    <tr><th class="py-1">Día</th><th class="py-1 text-right">Tickets</th><th class="py-1 text-right">Importe</th></tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($byDay as $row)
                        <tr>
                            <td class="py-1.5">{{ \Carbon\Carbon::parse($row->day)->locale('es')->isoFormat('ddd D/MM') }}</td>
                            <td class="py-1.5 text-right">{{ $row->tickets }}</td>
                            <td class="py-1.5 text-right">{{ Number::currency((float) $row->amount, 'EUR', 'es') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-center text-ink-muted">Sin ventas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    </div>

    <div class="overflow-x-auto rounded-card bg-card shadow-card">
        <table class="min-w-full divide-y divide-line text-sm">
            <thead class="bg-sunken text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted">
                <tr>
                    <th scope="col" class="px-4 py-3">Ticket</th>
                    <th scope="col" class="px-4 py-3">Fecha</th>
                    <th scope="col" class="px-4 py-3">Cajero/a</th>
                    <th scope="col" class="px-4 py-3">Pago</th>
                    <th scope="col" class="px-4 py-3 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($tickets as $sale)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('pos.tickets.show', $sale) }}" class="font-mono font-bold text-action hover:underline">{{ $sale->code }}</a>
                            <span class="block text-xs text-ink-muted">{{ $sale->items_count }} líneas</span>
                        </td>
                        <td class="px-4 py-3">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $sale->cashier?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $sale->payment?->method->label() }}</td>
                        <td class="px-4 py-3 text-right">{{ Number::currency((float) $sale->total, 'EUR', 'es') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-ink-muted">No hay tickets en este periodo.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $tickets->links() }}</div>
    </div>
</x-layouts.app>
