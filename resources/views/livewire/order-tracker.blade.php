@use('App\Enums\OrderStatus')
@use('App\Enums\Fulfillment')
<div @unless ($order->status->isFinal()) wire:poll.30s @endunless>
    @if ($order->status === OrderStatus::Ready)
        <div class="mb-4 rounded-card bg-action px-5 py-4 text-white" role="status">
            <p class="text-lg font-semibold">
                {{ $order->fulfillment === Fulfillment::Pickup ? '¡Tu pedido está listo para recoger!' : '¡Tu pedido está listo y saldrá en reparto!' }}
            </p>
            <p class="text-sm text-on-action">
                {{ $order->fulfillment === Fulfillment::Pickup ? 'Pásate por la tienda' : 'Te lo llevamos' }}
                el {{ $order->slot_date->locale('es')->isoFormat('dddd D') }} entre las {{ str_replace('-', ' y las ', $order->slot) }}.
            </p>
        </div>
    @endif

    @if ($order->status === OrderStatus::Cancelled)
        <div class="mb-4 rounded-card border border-red-200 bg-red-50 px-5 py-4 text-red-800" role="status">
            <p class="font-semibold">Pedido cancelado</p>
            @if ($cancellation?->note)
                <p class="text-sm">Motivo: {{ $cancellation->note }}</p>
            @endif
            <p class="text-xs">{{ $cancellation?->created_at?->format('d/m/Y H:i') }}</p>
        </div>
    @else
        <ol class="mb-6 grid grid-cols-4 gap-2" aria-label="Estado del pedido">
            @foreach ($steps as $step)
                <li class="text-center" @if ($step['current']) aria-current="step" @endif>
                    <div @class([
                        'mx-auto mb-2 h-2 rounded-full',
                        'bg-action' => $step['done'],
                        'bg-line' => ! $step['done'],
                    ])></div>
                    <p @class(['text-xs', 'font-semibold text-action' => $step['current'], 'text-ink-muted' => ! $step['current']])>
                        {{ $step['status']->label() }}
                    </p>
                    <p class="text-[11px] text-ink-muted">{{ $step['at']?->format('d/m H:i') ?? '—' }}</p>
                </li>
            @endforeach
        </ol>
    @endif
</div>
