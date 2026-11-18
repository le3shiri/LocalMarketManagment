@use('Illuminate\Support\Number')
<x-layouts.app :title="'Pedido '.$order->code">
    <div class="mx-auto max-w-2xl">
        <x-card>
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-ink-muted">Pedido</p>
                    <h1 class="text-[28px] font-extrabold tracking-tight text-ink">{{ $order->code }}</h1>
                </div>
                <a href="{{ route('orders.index') }}" class="text-sm text-ink-muted hover:underline">Mis pedidos</a>
            </div>

            <livewire:order-tracker :order="$order" />

            <dl class="mb-6 grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-ink-muted">Modalidad</dt><dd class="font-medium">{{ $order->fulfillment->label() }}</dd></div>
                <div>
                    <dt class="text-ink-muted">Franja</dt>
                    <dd class="font-medium">{{ ucfirst($order->slot_date->locale('es')->isoFormat('dddd D [de] MMMM')) }}, {{ $order->slot }}</dd>
                </div>
                @if ($order->delivery_address)
                    <div class="sm:col-span-2"><dt class="text-ink-muted">Dirección de entrega</dt><dd>{{ $order->delivery_address }}</dd></div>
                @endif
                <div><dt class="text-ink-muted">Pago</dt><dd class="font-medium">{{ $order->payment_method->label() }} {{ $order->fulfillment === \App\Enums\Fulfillment::Delivery ? 'al recibirlo' : 'al recogerlo' }}</dd></div>
                <div><dt class="text-ink-muted">Contacto</dt><dd>{{ $order->contact_name }} · {{ $order->contact_phone }}</dd></div>
            </dl>

            <table class="w-full text-sm">
                <tbody class="divide-y divide-line">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="py-2">{{ $item->product_name }}</td>
                            <td class="py-2 text-right text-ink-muted">
                                {{ \Illuminate\Support\Number::format($item->quantity, maxPrecision: 2, locale: 'es') }} {{ $item->unit_type->suffix() }}
                                × {{ Number::currency((float) $item->unit_price, 'EUR', 'es') }}
                            </td>
                            <td class="py-2 text-right">{{ Number::currency((float) $item->line_total, 'EUR', 'es') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-line">
                    <tr><td colspan="2" class="pt-3 text-right">Subtotal</td><td class="pt-3 text-right">{{ Number::currency((float) $order->subtotal, 'EUR', 'es') }}</td></tr>
                    <tr><td colspan="2" class="text-right">Envío</td><td class="text-right">{{ (float) $order->delivery_fee > 0 ? Number::currency((float) $order->delivery_fee, 'EUR', 'es') : 'Gratis' }}</td></tr>
                    <tr class="text-base font-semibold"><td colspan="2" class="pt-2 text-right">Total</td><td class="pt-2 text-right text-action">{{ Number::currency((float) $order->total, 'EUR', 'es') }}</td></tr>
                </tfoot>
            </table>
        </x-card>

        <div class="mt-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('catalog.index') }}" class="text-sm font-medium text-action hover:underline">Seguir comprando</a>
                <form method="POST" action="{{ route('orders.repeat', $order) }}">
                    @csrf
                    <button class="inline-flex min-h-11 items-center justify-center rounded-control bg-action px-4 text-sm font-bold text-on-action hover:bg-action-hover">Repetir pedido</button>
                </form>
            </div>
            @if ($order->status === \App\Enums\OrderStatus::Pending)
                <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('¿Seguro que quieres cancelar este pedido?')">
                    @csrf
                    <button class="text-sm text-ink-muted hover:text-red-600">Cancelar pedido</button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
