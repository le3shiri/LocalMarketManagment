@use('App\Enums\OrderStatus')
@use('App\Enums\Fulfillment')
<div @unless ($order->status->isFinal()) wire:poll.30s @endunless>
    @if ($order->status === OrderStatus::Ready)
        <div class="mb-5 flex items-start gap-3 rounded-card bg-success px-5 py-4 text-on-action" role="status">
            <svg class="mt-0.5 h-6 w-6 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
            <div>
                <p class="text-lg font-extrabold">
                    {{ $order->fulfillment === Fulfillment::Pickup ? '¡Tu pedido está listo para recoger!' : '¡Tu pedido está listo y saldrá en reparto!' }}
                </p>
                <p class="text-[15px]">
                    {{ $order->fulfillment === Fulfillment::Pickup ? 'Pásate por la tienda' : 'Te lo llevamos' }}
                    el {{ $order->slot_date->locale('es')->isoFormat('dddd D') }} entre las {{ str_replace('-', ' y las ', $order->slot) }}.
                </p>
            </div>
        </div>
    @endif

    @if ($order->status === OrderStatus::Cancelled)
        <div class="mb-5 rounded-card border border-red-200 bg-red-50 px-5 py-4 text-red-800" role="status">
            <p class="font-extrabold">Pedido cancelado</p>
            @if ($cancellation?->note)
                <p class="text-[15px]">Motivo: {{ $cancellation->note }}</p>
            @endif
            <p class="text-[13px]">{{ $cancellation?->created_at?->format('d/m/Y H:i') }}</p>
        </div>
    @else
        {{-- Live status: four steps; done steps fill in, the current one is in bold. --}}
        <ol class="mb-6 grid grid-cols-4 gap-2 rounded-card bg-card p-4 shadow-card" aria-label="Estado del pedido">
            @foreach ($steps as $step)
                <li class="text-center" @if ($step['current']) aria-current="step" @endif>
                    <div @class([
                        'mx-auto mb-2.5 h-1.5 rounded-full',
                        'bg-success' => $step['done'],
                        'bg-line' => ! $step['done'],
                    ])></div>
                    <p @class(['text-[13px]', 'font-extrabold text-ink' => $step['current'], 'font-semibold text-ink-soft' => ! $step['current'] && $step['done'], 'text-ink-muted' => ! $step['done']])>
                        {{ $step['status']->label() }}
                    </p>
                    <p class="text-xs text-ink-muted">{{ $step['at']?->format('d/m H:i') ?? '—' }}</p>
                </li>
            @endforeach
        </ol>
    @endif
</div>
