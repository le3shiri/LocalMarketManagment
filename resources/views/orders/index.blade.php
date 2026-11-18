@use('Illuminate\Support\Number')
<x-layouts.app title="Mis pedidos">
    <h1 class="mb-6 text-2xl font-bold text-stone-900">Mis pedidos</h1>

    @if ($orders->isEmpty())
        <div class="rounded-xl border border-dashed border-stone-300 p-10 text-center">
            <p class="text-stone-500">Todavía no has hecho ningún pedido.</p>
            <a href="{{ route('catalog.index') }}" class="mt-3 inline-block text-sm font-medium text-emerald-700 hover:underline">Ir al catálogo</a>
        </div>
    @else
        <ul class="divide-y divide-stone-100 rounded-xl border border-stone-200 bg-white">
            @foreach ($orders as $order)
                <li>
                    <a href="{{ route('orders.show', $order) }}" class="flex flex-wrap items-center gap-4 p-4 hover:bg-stone-50">
                        <div class="min-w-0 flex-1">
                            <p class="font-mono font-medium text-stone-900">{{ $order->code }}</p>
                            <p class="text-xs text-stone-500">
                                {{ $order->created_at->format('d/m/Y') }} · {{ $order->channelLabel() }} · {{ $order->items_count }} {{ Str::plural('producto', $order->items_count) }}
                            </p>
                        </div>
                        <p class="text-sm text-stone-600">{{ $order->fulfillment->label() }}<br><span class="text-xs">{{ $order->slot_date->format('d/m') }} · {{ $order->slot }}</span></p>
                        <p class="w-24 text-right font-medium">{{ Number::currency((float) $order->total, 'EUR', 'es') }}</p>
                        <x-order-status :status="$order->status" />
                    </a>
                    <form method="POST" action="{{ route('orders.repeat', $order) }}" class="-mt-2 px-4 pb-3 text-right">
                        @csrf
                        <button class="text-xs font-medium text-emerald-700 hover:underline">Repetir pedido</button>
                    </form>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $orders->links() }}</div>
    @endif
</x-layouts.app>
