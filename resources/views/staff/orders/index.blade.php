@use('Illuminate\Support\Number')
@use('App\Enums\OrderStatus')
<x-layouts.app title="Pedidos">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-[28px] font-extrabold tracking-tight text-ink">Pedidos</h1>
            <p class="text-sm text-ink-muted">Web, teléfono y WhatsApp en un único listado, ordenado por franja.</p>
        </div>
        <div class="flex gap-2">
            @if (Route::has('staff.orders.create'))
                <a href="{{ route('staff.orders.create', ['canal' => 'phone']) }}" class="rounded-control border border-line-strong bg-white px-3 py-2 text-sm font-medium text-ink-soft hover:bg-page">+ Pedido por teléfono</a>
                <a href="{{ route('staff.orders.create', ['canal' => 'whatsapp']) }}" class="inline-flex min-h-11 items-center justify-center rounded-control bg-action px-4 text-[15px] font-bold text-on-action hover:bg-action-hover">+ Pedido por WhatsApp</a>
            @endif
        </div>
    </div>

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <a href="{{ route('staff.orders.index') }}" @class([
            'rounded-full border px-3 py-1',
            'border-action bg-action text-white' => ! $status,
            'border-line-strong bg-white text-ink-soft' => $status,
        ])>Abiertos</a>
        @foreach (OrderStatus::cases() as $case)
            <a href="{{ route('staff.orders.index', ['estado' => $case->value]) }}" @class([
                'rounded-full border px-3 py-1',
                'border-action bg-action text-white' => $status === $case,
                'border-line-strong bg-white text-ink-soft' => $status !== $case,
            ])>{{ $case->label() }} <span class="opacity-70">{{ $counts[$case->value] ?? 0 }}</span></a>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-card border border-line bg-white">
        <table class="min-w-full divide-y divide-line text-sm">
            <thead class="bg-sunken text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted">
                <tr>
                    <th class="px-4 py-3">Pedido</th>
                    <th class="px-4 py-3">Cliente</th>
                    <th class="px-4 py-3">Franja</th>
                    <th class="px-4 py-3">Modalidad</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Siguiente paso</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('staff.orders.show', $order) }}" class="font-mono font-medium text-action hover:underline">{{ $order->code }}</a>
                            <p class="text-xs text-ink-muted">{{ $order->channelLabel() }} · {{ $order->items_count }} líneas</p>
                        </td>
                        <td class="px-4 py-3">{{ $order->contact_name }}<p class="text-xs text-ink-muted">{{ $order->contact_phone }}</p></td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $order->slot_date->format('d/m') }} · {{ $order->slot }}</td>
                        <td class="px-4 py-3">{{ $order->fulfillment->label() }}</td>
                        <td class="px-4 py-3 text-right">{{ Number::currency((float) $order->total, 'EUR', 'es') }}</td>
                        <td class="px-4 py-3"><x-order-status :status="$order->status" /></td>
                        <td class="px-4 py-3 text-right">
                            @if ($next = $order->status->next())
                                <form method="POST" action="{{ route('staff.orders.advance', $order) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $next->value }}">
                                    <button class="whitespace-nowrap rounded-control bg-action-soft px-2 py-1 text-xs font-medium text-action hover:bg-action-soft-hover">{{ $order->status->actionLabel() }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-ink-muted">No hay pedidos en este estado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
</x-layouts.app>
