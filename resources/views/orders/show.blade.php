@use('Illuminate\Support\Number')
<x-layouts.app :title="'Pedido '.$order->code">
    <div class="mx-auto max-w-2xl">
        <x-card>
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-stone-500">Pedido</p>
                    <h1 class="text-2xl font-bold text-stone-900">{{ $order->code }}</h1>
                </div>
                <a href="{{ route('orders.index') }}" class="text-sm text-stone-500 hover:underline">Mis pedidos</a>
            </div>

            <livewire:order-tracker :order="$order" />

            <dl class="mb-6 grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-stone-400">Modalidad</dt><dd class="font-medium">{{ $order->fulfillment->label() }}</dd></div>
                <div>
                    <dt class="text-stone-400">Franja</dt>
                    <dd class="font-medium">{{ ucfirst($order->slot_date->locale('es')->isoFormat('dddd D [de] MMMM')) }}, {{ $order->slot }}</dd>
                </div>
                @if ($order->delivery_address)
                    <div class="sm:col-span-2"><dt class="text-stone-400">Dirección de entrega</dt><dd>{{ $order->delivery_address }}</dd></div>
                @endif
                <div><dt class="text-stone-400">Pago</dt><dd class="font-medium">{{ $order->payment_method->label() }} {{ $order->fulfillment === \App\Enums\Fulfillment::Delivery ? 'al recibirlo' : 'al recogerlo' }}</dd></div>
                <div><dt class="text-stone-400">Contacto</dt><dd>{{ $order->contact_name }} · {{ $order->contact_phone }}</dd></div>
            </dl>

            <table class="w-full text-sm">
                <tbody class="divide-y divide-stone-100">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="py-2">{{ $item->product_name }}</td>
                            <td class="py-2 text-right text-stone-500">
                                {{ \Illuminate\Support\Number::format($item->quantity, maxPrecision: 2, locale: 'es') }} {{ $item->unit_type->suffix() }}
                                × {{ Number::currency((float) $item->unit_price, 'EUR', 'es') }}
                            </td>
                            <td class="py-2 text-right">{{ Number::currency((float) $item->line_total, 'EUR', 'es') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-stone-200">
                    <tr><td colspan="2" class="pt-3 text-right">Subtotal</td><td class="pt-3 text-right">{{ Number::currency((float) $order->subtotal, 'EUR', 'es') }}</td></tr>
                    <tr><td colspan="2" class="text-right">Envío</td><td class="text-right">{{ (float) $order->delivery_fee > 0 ? Number::currency((float) $order->delivery_fee, 'EUR', 'es') : 'Gratis' }}</td></tr>
                    <tr class="text-base font-semibold"><td colspan="2" class="pt-2 text-right">Total</td><td class="pt-2 text-right text-emerald-700">{{ Number::currency((float) $order->total, 'EUR', 'es') }}</td></tr>
                </tfoot>
            </table>
        </x-card>

        <div class="mt-4 flex items-center justify-between">
            <a href="{{ route('catalog.index') }}" class="text-sm font-medium text-emerald-700 hover:underline">Seguir comprando</a>
            @if ($order->status === \App\Enums\OrderStatus::Pending)
                <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('¿Seguro que quieres cancelar este pedido?')">
                    @csrf
                    <button class="text-sm text-stone-500 hover:text-red-600">Cancelar pedido</button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
