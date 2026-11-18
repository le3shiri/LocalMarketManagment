@use('Illuminate\Support\Number')
@use('App\Enums\OrderStatus')
@use('App\Enums\Fulfillment')
<x-layouts.app title="Supervisión de pedidos">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-stone-900">Supervisión de pedidos</h1>
        <p class="text-sm text-stone-500">Todos los canales. Los importes no incluyen pedidos cancelados.</p>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        @php($all = $byChannel->sum('orders'))
        <div class="rounded-xl border border-stone-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-stone-400">Total</p>
            <p class="mt-1 text-2xl font-bold text-stone-900">{{ $all }} <span class="text-sm font-normal text-stone-500">pedidos</span></p>
            <p class="text-sm font-medium text-emerald-700">{{ Number::currency((float) $byChannel->sum('amount'), 'EUR', 'es') }}</p>
        </div>
        @foreach ($channels as $value => $label)
            @php($row = $byChannel->get($value))
            <div class="rounded-xl border border-stone-200 bg-white p-4" data-channel="{{ $value }}">
                <p class="text-xs uppercase tracking-wide text-stone-400">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-stone-900">{{ $row->orders ?? 0 }}
                    @if ($all > 0)<span class="text-sm font-normal text-stone-500">· {{ round(100 * ($row->orders ?? 0) / $all) }} %</span>@endif
                </p>
                <p class="text-sm font-medium text-emerald-700">{{ Number::currency((float) ($row->amount ?? 0), 'EUR', 'es') }}</p>
            </div>
        @endforeach
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Código, cliente o teléfono" class="w-56 rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Buscar">
        <select name="channel" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Canal">
            <option value="">Todos los canales</option>
            @foreach ($channels as $value => $label)
                <option value="{{ $value }}" @selected(($filters['channel'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Estado">
            <option value="">Todos los estados</option>
            @foreach (OrderStatus::cases() as $case)
                <option value="{{ $case->value }}" @selected(($filters['status'] ?? '') === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
        <select name="fulfillment" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Modalidad">
            <option value="">Recogida y envío</option>
            @foreach (Fulfillment::cases() as $case)
                <option value="{{ $case->value }}" @selected(($filters['fulfillment'] ?? '') === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Desde">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Hasta">
        <x-button variant="secondary">Filtrar</x-button>
        <x-csv-link :href="route('admin.orders.export', request()->query())" />
        @if (array_filter($filters))
            <a href="{{ route('admin.orders.index') }}" class="px-2 py-2 text-sm text-stone-500 hover:underline">Quitar filtros</a>
        @endif
    </form>
    @error('to') <p class="mb-3 text-xs text-red-600">{{ $message }}</p> @enderror

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="overflow-x-auto rounded-xl border border-stone-200 bg-white lg:col-span-2">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                    <tr>
                        <th class="px-4 py-3">Pedido</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Franja</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('staff.orders.show', $order) }}" class="font-mono font-medium text-emerald-700 hover:underline">{{ $order->code }}</a>
                                <p class="text-xs text-stone-500">{{ $order->channelLabel() }} · {{ $order->fulfillment->label() }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $order->contact_name }}<p class="text-xs text-stone-500">{{ $order->contact_phone }}</p></td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $order->slot_date->format('d/m/Y') }}<p class="text-xs text-stone-500">{{ $order->slot }}</p></td>
                            <td class="px-4 py-3 text-right">{{ Number::currency((float) $order->total, 'EUR', 'es') }}</td>
                            <td class="px-4 py-3"><x-order-status :status="$order->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-stone-500">No hay pedidos con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $orders->links() }}</div>
        </div>

        <x-card title="Pedidos por día y estado" class="h-fit">
            <table class="w-full text-xs">
                <thead class="text-stone-400">
                    <tr>
                        <th class="py-1 text-left">Día</th>
                        @foreach (OrderStatus::cases() as $case)
                            <th class="py-1 text-right" title="{{ $case->label() }}">{{ mb_substr($case->label(), 0, 4) }}.</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($byDay as $day => $rows)
                        <tr>
                            <td class="py-1.5">{{ \Carbon\Carbon::parse($day)->format('d/m') }}</td>
                            @foreach (OrderStatus::cases() as $case)
                                @php($count = $rows->first(fn ($r) => $r->status === $case)?->orders ?? 0)
                                <td @class(['py-1.5 text-right', 'text-stone-300' => $count === 0, 'font-medium' => $count > 0])>{{ $count }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-4 text-center text-stone-500">Sin datos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    </div>
</x-layouts.app>
