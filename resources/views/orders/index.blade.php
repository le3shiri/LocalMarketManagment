@use('Illuminate\Support\Number')
<x-layouts.app title="Mis pedidos">
    <x-page-header title="Mis pedidos" subtitle="Sigue el estado en directo o repite un pedido con los precios de hoy." />

    @if ($orders->isEmpty())
        <section class="mx-auto max-w-xl rounded-shelf border border-line bg-card px-8 py-12 text-center">
            <h2 class="text-2xl font-extrabold tracking-tight">Todavía no has hecho ningún pedido.</h2>
            <p class="mt-2 text-base text-ink-soft">Cuando hagas uno, aquí podrás seguirlo paso a paso.</p>
            <a href="{{ route('catalog.index') }}" class="mt-6 inline-flex h-12 items-center rounded-control bg-action px-6 text-[15px] font-bold text-on-action hover:bg-action-hover">Ir al catálogo</a>
        </section>
    @else
        <ul class="space-y-3">
            @foreach ($orders as $order)
                <li class="rounded-card bg-card shadow-card">
                    <a href="{{ route('orders.show', $order) }}" class="flex flex-wrap items-center gap-4 rounded-t-card p-4 hover:bg-page">
                        <div class="min-w-0 flex-1">
                            <p class="font-mono text-[15px] font-bold text-ink">{{ $order->code }}</p>
                            <p class="text-[13px] text-ink-muted">
                                {{ $order->created_at->format('d/m/Y') }} · {{ $order->channelLabel() }} · {{ $order->items_count }} {{ Str::plural('producto', $order->items_count) }}
                            </p>
                        </div>
                        <p class="text-sm text-ink-soft">{{ $order->fulfillment->label() }}<br><span class="text-[13px] text-ink-muted">{{ $order->slot_date->format('d/m') }} · {{ $order->slot }}</span></p>
                        <p class="w-24 text-right text-lg font-extrabold tracking-tight">{{ Number::currency((float) $order->total, 'EUR', 'es') }}</p>
                        <x-order-status :status="$order->status" />
                    </a>
                    <form method="POST" action="{{ route('orders.repeat', $order) }}" class="flex justify-end border-t border-line px-4 py-1.5">
                        @csrf
                        <button class="inline-flex min-h-11 items-center gap-1.5 rounded-control px-3 text-sm font-bold text-action hover:bg-action-soft">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12a8 8 0 0 1 14-5.3L20 9M20 4v5h-5M20 12a8 8 0 0 1-14 5.3L4 15M4 20v-5h5"/></svg>
                            Repetir pedido
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>
        <div class="mt-5">{{ $orders->links() }}</div>
    @endif
</x-layouts.app>
