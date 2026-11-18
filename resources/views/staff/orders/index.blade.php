@use('Illuminate\Support\Number')
@use('App\Enums\OrderStatus')
<x-layouts.app title="Pedidos">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-stone-900">Pedidos</h1>
            <p class="text-sm text-stone-500">Web, teléfono y WhatsApp en un único listado, ordenado por franja.</p>
        </div>
        <div class="flex gap-2">
            @if (Route::has('staff.orders.create'))
                <a href="{{ route('staff.orders.create', ['canal' => 'phone']) }}" class="rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50">+ Pedido por teléfono</a>
                <a href="{{ route('staff.orders.create', ['canal' => 'whatsapp']) }}" class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">+ Pedido por WhatsApp</a>
            @endif
        </div>
    </div>

    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <a href="{{ route('staff.orders.index') }}" @class([
            'rounded-full border px-3 py-1',
            'border-emerald-600 bg-emerald-600 text-white' => ! $status,
            'border-stone-300 bg-white text-stone-600' => $status,
        ])>Abiertos</a>
        @foreach (OrderStatus::cases() as $case)
            <a href="{{ route('staff.orders.index', ['estado' => $case->value]) }}" @class([
                'rounded-full border px-3 py-1',
                'border-emerald-600 bg-emerald-600 text-white' => $status === $case,
                'border-stone-300 bg-white text-stone-600' => $status !== $case,
            ])>{{ $case->label() }} <span class="opacity-70">{{ $counts[$case->value] ?? 0 }}</span></a>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-xl border border-stone-200 bg-white">
        <table class="min-w-full divide-y divide-stone-200 text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
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
            <tbody class="divide-y divide-stone-100">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('staff.orders.show', $order) }}" class="font-mono font-medium text-emerald-700 hover:underline">{{ $order->code }}</a>
                            <p class="text-xs text-stone-500">{{ $order->channelLabel() }} · {{ $order->items_count }} líneas</p>
                        </td>
                        <td class="px-4 py-3">{{ $order->contact_name }}<p class="text-xs text-stone-500">{{ $order->contact_phone }}</p></td>
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
                                    <button class="whitespace-nowrap rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-100">{{ $order->status->actionLabel() }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-stone-500">No hay pedidos en este estado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
</x-layouts.app>
