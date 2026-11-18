@use('Illuminate\Support\Number')
<x-layouts.app :title="'Pedido '.$order->code">
    <div class="mx-auto max-w-2xl">
        <x-card>
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-[13px] font-bold uppercase tracking-[0.06em] text-ink-muted">Pedido</p>
                    <h1 class="font-mono text-[28px] font-extrabold tracking-tight text-ink">{{ $order->code }}</h1>
                </div>
                <a href="{{ route('orders.index') }}" class="inline-flex min-h-11 items-center gap-1.5 text-[15px] font-bold text-action hover:underline"><span aria-hidden="true">←</span> Mis pedidos</a>
            </div>

            <livewire:order-tracker :order="$order" />

            <dl class="mb-6 grid gap-4 rounded-card bg-page p-4 text-[15px] sm:grid-cols-2">
                <div><dt class="text-[13px] font-semibold text-ink-muted">Modalidad</dt><dd class="font-bold">{{ $order->fulfillment->label() }}</dd></div>
                <div>
                    <dt class="text-[13px] font-semibold text-ink-muted">Franja</dt>
                    <dd class="font-bold">{{ ucfirst($order->slot_date->locale('es')->isoFormat('dddd D [de] MMMM')) }}, {{ $order->slot }}</dd>
                </div>
                @if ($order->delivery_address)
                    <div class="sm:col-span-2"><dt class="text-[13px] font-semibold text-ink-muted">Dirección de entrega</dt><dd>{{ $order->delivery_address }}</dd></div>
                @endif
                <div><dt class="text-[13px] font-semibold text-ink-muted">Pago</dt><dd class="font-bold">{{ $order->payment_method->label() }} {{ $order->fulfillment === \App\Enums\Fulfillment::Delivery ? 'al recibirlo' : 'al recogerlo' }}</dd></div>
                <div><dt class="text-[13px] font-semibold text-ink-muted">Contacto</dt><dd>{{ $order->contact_name }} · {{ $order->contact_phone }}</dd></div>
            </dl>

            <table class="w-full text-[15px]">
                <tbody class="divide-y divide-line">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="py-3 font-semibold">{{ $item->product_name }}</td>
                            <td class="py-3 text-right text-ink-muted">
                                {{ \Illuminate\Support\Number::format($item->quantity, maxPrecision: 2, locale: 'es') }} {{ $item->unit_type->suffix() }}
                                × {{ Number::currency((float) $item->unit_price, 'EUR', 'es') }}
                            </td>
                            <td class="py-3 text-right font-bold">{{ Number::currency((float) $item->line_total, 'EUR', 'es') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-line">
                    <tr><td colspan="2" class="pt-3 text-right">Subtotal</td><td class="pt-3 text-right">{{ Number::currency((float) $order->subtotal, 'EUR', 'es') }}</td></tr>
                    <tr><td colspan="2" class="text-right">Envío</td><td class="text-right">{{ (float) $order->delivery_fee > 0 ? Number::currency((float) $order->delivery_fee, 'EUR', 'es') : 'Gratis' }}</td></tr>
                    <tr><td colspan="2" class="pt-3 text-right font-bold">Total</td><td class="pt-3 text-right text-2xl font-extrabold tracking-tight text-ink">{{ Number::currency((float) $order->total, 'EUR', 'es') }}</td></tr>
                </tfoot>
            </table>
        </x-card>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-4">
                <a href="{{ route('catalog.index') }}" class="inline-flex min-h-11 items-center text-[15px] font-bold text-action hover:underline">Seguir comprando</a>
                <form method="POST" action="{{ route('orders.repeat', $order) }}">
                    @csrf
                    <button class="inline-flex min-h-11 items-center justify-center rounded-control bg-action px-4 text-sm font-bold text-on-action hover:bg-action-hover">Repetir pedido</button>
                </form>
            </div>
            @if ($order->status === \App\Enums\OrderStatus::Pending)
                <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('¿Seguro que quieres cancelar este pedido?')">
                    @csrf
                    <button class="inline-flex min-h-11 items-center rounded-control border border-red-200 px-4 text-sm font-bold text-red-700 hover:bg-red-50">Cancelar pedido</button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
