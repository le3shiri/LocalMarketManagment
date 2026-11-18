@use('Illuminate\Support\Number')
<x-layouts.app :title="'Pedido '.$order->code">
    <a href="{{ route('staff.orders.index') }}" class="text-sm text-ink-muted hover:underline">← Volver a pedidos</a>

    <div class="mt-3 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card>
                <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-ink-muted">{{ $order->channelLabel() }} · {{ $order->created_at->format('d/m/Y H:i') }}</p>
                        <h1 class="font-mono text-[28px] font-extrabold tracking-tight text-ink">{{ $order->code }}</h1>
                    </div>
                    <x-order-status :status="$order->status" class="text-sm" />
                </div>

                <dl class="mb-4 grid gap-3 text-sm sm:grid-cols-3">
                    <div><dt class="text-ink-muted">Cliente</dt><dd class="font-medium">{{ $order->contact_name }}</dd><dd>{{ $order->contact_phone }}</dd></div>
                    <div><dt class="text-ink-muted">Modalidad</dt><dd class="font-medium">{{ $order->fulfillment->label() }}</dd>@if ($order->delivery_address)<dd>{{ $order->delivery_address }}</dd>@endif</div>
                    <div><dt class="text-ink-muted">Franja</dt><dd class="font-medium">{{ $order->slot_date->format('d/m/Y') }}, {{ $order->slot }}</dd></div>
                    <div>
                        <dt class="text-ink-muted">Pago</dt>
                        @if ($order->payment)
                            <dd class="font-medium text-action">Cobrado · {{ $order->payment->method->label() }} · {{ Number::currency((float) $order->payment->amount, 'EUR', 'es') }}</dd>
                        @else
                            <dd class="font-medium">{{ $order->payment_method->label() }} en la entrega</dd>
                        @endif
                    </div>
                </dl>
                @if ($order->creator)
                    <p class="mb-2 text-xs text-ink-muted">Registrado por {{ $order->creator->name }}</p>
                @endif
                @if ($order->source_message)
                    <figure class="mb-4 rounded-control border-l-4 border-action bg-action-soft px-3 py-2 text-sm">
                        <blockquote class="whitespace-pre-line text-ink-soft">{{ $order->source_message }}</blockquote>
                        @if ($order->source_reference)
                            <figcaption class="mt-1 text-xs text-ink-muted">{{ $order->source_reference }}</figcaption>
                        @endif
                    </figure>
                @endif
                @if ($order->notes)
                    <p class="mb-4 rounded-control bg-sunken px-3 py-2 text-sm"><span class="text-ink-muted">Observaciones:</span> {{ $order->notes }}</p>
                @endif

                <table class="w-full text-sm">
                    <thead class="text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted">
                        <tr><th class="py-2">Producto</th><th class="py-2 text-right">Cantidad</th><th class="py-2 text-right">Precio</th><th class="py-2 text-right">Importe</th></tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="py-2">{{ $item->product_name }} <span class="font-mono text-xs text-ink-muted">{{ $item->sku }}</span></td>
                                <td class="py-2 text-right">
                                    {{ Number::format($item->delivered_quantity ?? $item->quantity, maxPrecision: 3, locale: 'es') }} {{ $item->unit_type->suffix() }}
                                    @if ($item->delivered_quantity !== null && abs($item->delivered_quantity - $item->quantity) > 0.0005)
                                        <span class="block text-xs text-ink-muted">pedido {{ Number::format($item->quantity, maxPrecision: 3, locale: 'es') }}</span>
                                    @endif
                                </td>
                                <td class="py-2 text-right">{{ Number::currency((float) $item->unit_price, 'EUR', 'es') }}</td>
                                <td class="py-2 text-right">{{ Number::currency((float) $item->line_total, 'EUR', 'es') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-line font-semibold">
                        <tr><td colspan="3" class="pt-3 text-right">Total</td><td class="pt-3 text-right">{{ Number::currency((float) $order->total, 'EUR', 'es') }}</td></tr>
                    </tfoot>
                </table>
            </x-card>
        </div>

        <div class="space-y-6">
            @if ($next = $order->status->next())
                <x-card title="Siguiente paso">
                    <form method="POST" action="{{ route('staff.orders.advance', $order) }}" class="space-y-3">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $next->value }}">
                        @if ($next === \App\Enums\OrderStatus::Delivered)
                            @foreach ($order->items->where('unit_type', \App\Enums\UnitType::Weight) as $item)
                                <div class="space-y-1">
                                    <label for="weight-{{ $item->id }}" class="block text-sm font-medium text-ink-soft">
                                        Peso real · {{ $item->product_name }}
                                        <span class="font-normal text-ink-muted">(pedido {{ Number::format($item->quantity, maxPrecision: 2, locale: 'es') }} kg)</span>
                                    </label>
                                    <input id="weight-{{ $item->id }}" name="weights[{{ $item->id }}]" type="number" step="0.005" min="0.005"
                                           value="{{ old("weights.{$item->id}", $item->quantity) }}"
                                           class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
                                    @error("weights.{$item->id}") <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            @endforeach
                            <div class="space-y-1">
                                <label for="payment_method" class="block text-sm font-medium text-ink-soft">Cobrado con</label>
                                <select id="payment_method" name="payment_method" class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
                                    @foreach (\App\Enums\PaymentMethod::cases() as $method)
                                        <option value="{{ $method->value }}" @selected($order->payment_method === $method)>{{ $method->label() }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-ink-muted">Se registra el cobro del total final (con el peso real).</p>
                            </div>
                        @endif
                        <x-input name="note" label="Nota (opcional)" />
                        <x-button class="w-full">{{ $order->status->actionLabel() }}</x-button>
                    </form>
                </x-card>
            @endif

            @unless ($order->status->isFinal())
                <x-card title="Cancelar pedido">
                    <form method="POST" action="{{ route('staff.orders.cancel', $order) }}" class="space-y-3"
                          onsubmit="return confirm({{ Js::from('¿Cancelar el pedido '.$order->code.'? El stock reservado se liberará.') }})">
                        @csrf
                        <x-input name="reason" label="Motivo (obligatorio)" required placeholder="Ej.: la clienta ha llamado para anularlo" />
                        <x-button variant="danger" class="w-full">Cancelar pedido</x-button>
                    </form>
                </x-card>
            @endunless

            <x-card title="Historial del pedido">
                <ol class="space-y-3 border-l-2 border-line pl-4 text-sm">
                    @foreach ($order->statusChanges as $change)
                        <li class="relative">
                            <span class="absolute -left-[1.4rem] top-1 h-2.5 w-2.5 rounded-full bg-action"></span>
                            <p class="font-medium">{{ $change->to_status->label() }}</p>
                            <p class="text-xs text-ink-muted">{{ $change->created_at->format('d/m H:i') }} · {{ $change->user?->name ?? 'Sistema' }}</p>
                            @if ($change->note)<p class="text-xs text-ink-soft">{{ $change->note }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </x-card>
        </div>
    </div>
</x-layouts.app>
