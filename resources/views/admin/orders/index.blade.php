@use('Illuminate\Support\Number')
@use('App\Enums\OrderStatus')
@use('App\Enums\Fulfillment')
<x-layouts.app title="Supervisión de pedidos">
    <div class="mb-6">
        <h1 class="text-[28px] font-extrabold tracking-tight text-ink">Supervisión de pedidos</h1>
        <p class="text-sm text-ink-muted">Todos los canales. Los importes no incluyen pedidos cancelados.</p>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        @php($all = $byChannel->sum('orders'))
        <div class="rounded-card border border-line bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-ink-muted">Total</p>
            <p class="mt-1 text-[28px] font-extrabold tracking-tight text-ink">{{ $all }} <span class="text-sm font-normal text-ink-muted">pedidos</span></p>
            <p class="text-sm font-medium text-action">{{ Number::currency((float) $byChannel->sum('amount'), 'EUR', 'es') }}</p>
        </div>
        @foreach ($channels as $value => $label)
            @php($row = $byChannel->get($value))
            <div class="rounded-card border border-line bg-white p-4" data-channel="{{ $value }}">
                <p class="text-xs uppercase tracking-wide text-ink-muted">{{ $label }}</p>
                <p class="mt-1 text-[28px] font-extrabold tracking-tight text-ink">{{ $row->orders ?? 0 }}
                    @if ($all > 0)<span class="text-sm font-normal text-ink-muted">· {{ round(100 * ($row->orders ?? 0) / $all) }} %</span>@endif
                </p>
                <p class="text-sm font-medium text-action">{{ Number::currency((float) ($row->amount ?? 0), 'EUR', 'es') }}</p>
            </div>
        @endforeach
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Código, cliente o teléfono" class="w-56 min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Buscar">
        <select name="channel" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Canal">
            <option value="">Todos los canales</option>
            @foreach ($channels as $value => $label)
                <option value="{{ $value }}" @selected(($filters['channel'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Estado">
            <option value="">Todos los estados</option>
            @foreach (OrderStatus::cases() as $case)
                <option value="{{ $case->value }}" @selected(($filters['status'] ?? '') === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
        <select name="fulfillment" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Modalidad">
            <option value="">Recogida y envío</option>
            @foreach (Fulfillment::cases() as $case)
                <option value="{{ $case->value }}" @selected(($filters['fulfillment'] ?? '') === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Desde">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Hasta">
        <x-button variant="secondary">Filtrar</x-button>
        <x-csv-link :href="route('admin.orders.export', request()->query())" />
        @if (array_filter($filters))
            <a href="{{ route('admin.orders.index') }}" class="px-2 py-2 text-sm text-ink-muted hover:underline">Quitar filtros</a>
        @endif
    </form>
    @error('to') <p class="mb-3 text-xs text-red-600">{{ $message }}</p> @enderror

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="overflow-x-auto rounded-card border border-line bg-white lg:col-span-2">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="bg-sunken text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted">
                    <tr>
                        <th class="px-4 py-3">Pedido</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Franja</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('staff.orders.show', $order) }}" class="font-mono font-medium text-action hover:underline">{{ $order->code }}</a>
                                <p class="text-xs text-ink-muted">{{ $order->channelLabel() }} · {{ $order->fulfillment->label() }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $order->contact_name }}<p class="text-xs text-ink-muted">{{ $order->contact_phone }}</p></td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $order->slot_date->format('d/m/Y') }}<p class="text-xs text-ink-muted">{{ $order->slot }}</p></td>
                            <td class="px-4 py-3 text-right">{{ Number::currency((float) $order->total, 'EUR', 'es') }}</td>
                            <td class="px-4 py-3"><x-order-status :status="$order->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-ink-muted">No hay pedidos con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $orders->links() }}</div>
        </div>

        <x-card title="Pedidos por día y estado" class="h-fit">
            <table class="w-full text-xs">
                <thead class="text-ink-muted">
                    <tr>
                        <th class="py-1 text-left">Día</th>
                        @foreach (OrderStatus::cases() as $case)
                            <th class="py-1 text-right" title="{{ $case->label() }}">{{ mb_substr($case->label(), 0, 4) }}.</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($byDay as $day => $rows)
                        <tr>
                            <td class="py-1.5">{{ \Carbon\Carbon::parse($day)->format('d/m') }}</td>
                            @foreach (OrderStatus::cases() as $case)
                                @php($count = $rows->first(fn ($r) => $r->status === $case)?->orders ?? 0)
                                <td @class(['py-1.5 text-right', 'text-line-dashed' => $count === 0, 'font-medium' => $count > 0])>{{ $count }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-4 text-center text-ink-muted">Sin datos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    </div>
</x-layouts.app>
