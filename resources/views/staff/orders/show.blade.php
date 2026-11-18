@use('Illuminate\Support\Number')
<x-layouts.app :title="'Pedido '.$order->code">
    <a href="{{ route('staff.orders.index') }}" class="text-sm text-stone-500 hover:underline">← Volver a pedidos</a>

    <div class="mt-3 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card>
                <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-stone-500">{{ $order->channelLabel() }} · {{ $order->created_at->format('d/m/Y H:i') }}</p>
                        <h1 class="font-mono text-2xl font-bold text-stone-900">{{ $order->code }}</h1>
                    </div>
                    <x-order-status :status="$order->status" class="text-sm" />
                </div>

                <dl class="mb-4 grid gap-3 text-sm sm:grid-cols-3">
                    <div><dt class="text-stone-400">Cliente</dt><dd class="font-medium">{{ $order->contact_name }}</dd><dd>{{ $order->contact_phone }}</dd></div>
                    <div><dt class="text-stone-400">Modalidad</dt><dd class="font-medium">{{ $order->fulfillment->label() }}</dd>@if ($order->delivery_address)<dd>{{ $order->delivery_address }}</dd>@endif</div>
                    <div><dt class="text-stone-400">Franja</dt><dd class="font-medium">{{ $order->slot_date->format('d/m/Y') }}, {{ $order->slot }}</dd></div>
                </dl>
                @if ($order->notes)
                    <p class="mb-4 rounded-lg bg-stone-50 px-3 py-2 text-sm"><span class="text-stone-400">Observaciones:</span> {{ $order->notes }}</p>
                @endif

                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase text-stone-400">
                        <tr><th class="py-2">Producto</th><th class="py-2 text-right">Cantidad</th><th class="py-2 text-right">Precio</th><th class="py-2 text-right">Importe</th></tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="py-2">{{ $item->product_name }} <span class="font-mono text-xs text-stone-400">{{ $item->sku }}</span></td>
                                <td class="py-2 text-right">
                                    {{ Number::format($item->delivered_quantity ?? $item->quantity, maxPrecision: 3, locale: 'es') }} {{ $item->unit_type->suffix() }}
                                    @if ($item->delivered_quantity !== null && abs($item->delivered_quantity - $item->quantity) > 0.0005)
                                        <span class="block text-xs text-stone-400">pedido {{ Number::format($item->quantity, maxPrecision: 3, locale: 'es') }}</span>
                                    @endif
                                </td>
                                <td class="py-2 text-right">{{ Number::currency((float) $item->unit_price, 'EUR', 'es') }}</td>
                                <td class="py-2 text-right">{{ Number::currency((float) $item->line_total, 'EUR', 'es') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-stone-200 font-semibold">
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
                                    <label for="weight-{{ $item->id }}" class="block text-sm font-medium text-stone-700">
                                        Peso real · {{ $item->product_name }}
                                        <span class="font-normal text-stone-400">(pedido {{ Number::format($item->quantity, maxPrecision: 2, locale: 'es') }} kg)</span>
                                    </label>
                                    <input id="weight-{{ $item->id }}" name="weights[{{ $item->id }}]" type="number" step="0.005" min="0.005"
                                           value="{{ old("weights.{$item->id}", $item->quantity) }}"
                                           class="block w-full rounded-lg border border-stone-300 px-3 py-2 text-sm">
                                    @error("weights.{$item->id}") <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            @endforeach
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
                <ol class="space-y-3 border-l-2 border-stone-200 pl-4 text-sm">
                    @foreach ($order->statusChanges as $change)
                        <li class="relative">
                            <span class="absolute -left-[1.4rem] top-1 h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            <p class="font-medium">{{ $change->to_status->label() }}</p>
                            <p class="text-xs text-stone-500">{{ $change->created_at->format('d/m H:i') }} · {{ $change->user?->name ?? 'Sistema' }}</p>
                            @if ($change->note)<p class="text-xs text-stone-600">{{ $change->note }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </x-card>
        </div>
    </div>
</x-layouts.app>
