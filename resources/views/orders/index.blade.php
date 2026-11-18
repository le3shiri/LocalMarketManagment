@use('Illuminate\Support\Number')
<x-layouts.app title="Mis pedidos">
    <h1 class="mb-6 text-[28px] font-extrabold tracking-tight text-ink">Mis pedidos</h1>

    @if ($orders->isEmpty())
        <div class="rounded-card border border-dashed border-line-strong p-10 text-center">
            <p class="text-ink-muted">Todavía no has hecho ningún pedido.</p>
            <a href="{{ route('catalog.index') }}" class="mt-3 inline-block text-sm font-medium text-action hover:underline">Ir al catálogo</a>
        </div>
    @else
        <ul class="divide-y divide-line rounded-card border border-line bg-white">
            @foreach ($orders as $order)
                <li>
                    <a href="{{ route('orders.show', $order) }}" class="flex flex-wrap items-center gap-4 p-4 hover:bg-page">
                        <div class="min-w-0 flex-1">
                            <p class="font-mono font-medium text-ink">{{ $order->code }}</p>
                            <p class="text-xs text-ink-muted">
                                {{ $order->created_at->format('d/m/Y') }} · {{ $order->channelLabel() }} · {{ $order->items_count }} {{ Str::plural('producto', $order->items_count) }}
                            </p>
                        </div>
                        <p class="text-sm text-ink-soft">{{ $order->fulfillment->label() }}<br><span class="text-xs">{{ $order->slot_date->format('d/m') }} · {{ $order->slot }}</span></p>
                        <p class="w-24 text-right font-medium">{{ Number::currency((float) $order->total, 'EUR', 'es') }}</p>
                        <x-order-status :status="$order->status" />
                    </a>
                    <form method="POST" action="{{ route('orders.repeat', $order) }}" class="-mt-2 px-4 pb-3 text-right">
                        @csrf
                        <button class="text-xs font-medium text-action hover:underline">Repetir pedido</button>
                    </form>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $orders->links() }}</div>
    @endif
</x-layouts.app>
