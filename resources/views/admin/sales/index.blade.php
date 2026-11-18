@use('Illuminate\Support\Number')
@use('App\Enums\PaymentMethod')
<x-layouts.app title="Ventas y pagos">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-stone-900">Ventas y pagos</h1>
            <p class="text-sm text-stone-500">Del {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}</p>
        </div>
        <form method="GET" class="flex flex-wrap items-end gap-2">
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Desde">
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Hasta">
            <select name="cashier" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Empleado">
                <option value="">Todo el personal</option>
                @foreach ($cashiers as $cashier)
                    <option value="{{ $cashier->id }}" @selected(($filters['cashier'] ?? null) == $cashier->id)>{{ $cashier->name }}</option>
                @endforeach
            </select>
            <select name="method" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Método">
                <option value="">Todos los métodos</option>
                @foreach (PaymentMethod::cases() as $method)
                    <option value="{{ $method->value }}" @selected(($filters['method'] ?? '') === $method->value)>{{ $method->label() }}</option>
                @endforeach
            </select>
            <x-button variant="secondary">Filtrar</x-button>
        </form>
    </div>
    @error('to') <p class="mb-3 text-xs text-red-600">{{ $message }}</p> @enderror

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-stone-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-stone-400">Devoluciones</p>
            <p class="mt-1 text-2xl font-bold text-red-700" data-refunds-total>-{{ Number::currency($refundsTotal, 'EUR', 'es') }}</p>
        </div>
        <div class="rounded-xl border border-stone-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-stone-400">Ventas en caja</p>
            <p class="mt-1 text-2xl font-bold" data-sales-total>{{ Number::currency($salesTotal, 'EUR', 'es') }}</p>
        </div>
        <div class="rounded-xl border border-stone-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-stone-400">Tickets</p>
            <p class="mt-1 text-2xl font-bold" data-ticket-count>{{ $ticketCount }}</p>
        </div>
        <div class="rounded-xl border border-stone-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-stone-400">Ticket medio</p>
            <p class="mt-1 text-2xl font-bold" data-average>{{ Number::currency($averageTicket, 'EUR', 'es') }}</p>
        </div>
    </div>

    <div class="mb-6 grid gap-6 lg:grid-cols-2">
        <x-card title="Cobros por método">
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase text-stone-400">
                    <tr><th class="py-1">Método</th><th class="py-1">Origen</th><th class="py-1 text-right">Operaciones</th><th class="py-1 text-right">Importe</th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($byMethod as $method => $rows)
                        @foreach ($rows as $row)
                            <tr>
                                <td class="py-1.5">{{ $loop->first ? PaymentMethod::from($method)->label() : '' }}</td>
                                <td class="py-1.5 text-stone-500">{{ $sourceLabels[$row->payable_type] ?? 'Otros' }}</td>
                                <td class="py-1.5 text-right">{{ $row->operations }}</td>
                                <td class="py-1.5 text-right">{{ Number::currency((float) $row->amount, 'EUR', 'es') }}</td>
                            </tr>
                        @endforeach
                        <tr class="font-semibold" data-method-total="{{ $method }}">
                            <td colspan="3" class="py-1.5 text-right">Total {{ PaymentMethod::from($method)->label() }}</td>
                            <td class="py-1.5 text-right">{{ Number::currency((float) $rows->sum('amount'), 'EUR', 'es') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-center text-stone-500">Sin cobros en este periodo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        <x-card title="Ventas en caja por día">
            <table class="w-full text-sm">
                <thead class="text-left text-xs uppercase text-stone-400">
                    <tr><th class="py-1">Día</th><th class="py-1 text-right">Tickets</th><th class="py-1 text-right">Importe</th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($byDay as $row)
                        <tr>
                            <td class="py-1.5">{{ \Carbon\Carbon::parse($row->day)->locale('es')->isoFormat('ddd D/MM') }}</td>
                            <td class="py-1.5 text-right">{{ $row->tickets }}</td>
                            <td class="py-1.5 text-right">{{ Number::currency((float) $row->amount, 'EUR', 'es') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-center text-stone-500">Sin ventas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    </div>

    <div class="overflow-x-auto rounded-xl border border-stone-200 bg-white">
        <table class="min-w-full divide-y divide-stone-200 text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                <tr>
                    <th class="px-4 py-3">Ticket</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Cajero/a</th>
                    <th class="px-4 py-3">Pago</th>
                    <th class="px-4 py-3 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($tickets as $sale)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('pos.tickets.show', $sale) }}" class="font-mono font-medium text-emerald-700 hover:underline">{{ $sale->code }}</a>
                            <span class="block text-xs text-stone-500">{{ $sale->items_count }} líneas</span>
                        </td>
                        <td class="px-4 py-3">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $sale->cashier?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $sale->payment?->method->label() }}</td>
                        <td class="px-4 py-3 text-right">{{ Number::currency((float) $sale->total, 'EUR', 'es') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-stone-500">No hay tickets en este periodo.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $tickets->links() }}</div>
    </div>
</x-layouts.app>
