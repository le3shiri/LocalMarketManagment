@use('Illuminate\Support\Number')
<x-layouts.app :title="'Devolución del ticket '.$sale->code">
    <a href="{{ route('pos.tickets.show', $sale) }}" class="inline-flex min-h-11 items-center text-[15px] font-bold text-action hover:underline">← Ticket {{ $sale->code }}</a>

    <div class="mx-auto mt-3 max-w-3xl">
        <x-card :title="'Devolución del ticket '.$sale->code">
            <p class="mb-4 text-sm text-ink-muted">
                Venta del {{ $sale->created_at->format('d/m/Y H:i') }} · pagada con {{ $sale->payment?->method->label() ?? '—' }}.
                Se puede devolver hasta el {{ $deadline->format('d/m/Y') }}. El reembolso se hace con el mismo método de pago.
            </p>

            @if ($deadline->isPast())
                <p class="rounded-control bg-red-50 px-3 py-2 text-sm text-red-800">Este ticket ya no admite devoluciones.</p>
            @else
                <form method="POST" action="{{ route('pos.refunds.store', $sale) }}" class="space-y-4">
                    @csrf
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted">
                            <tr><th class="py-2">Producto</th><th class="py-2 text-right">Vendido</th><th class="py-2 text-right">Se puede devolver</th><th class="py-2 text-right">Devolver</th></tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($sale->items as $item)
                                @php($refundable = $item->refundableQuantity())
                                <tr>
                                    <td class="py-2">
                                        {{ $item->product_name }}
                                        @if ($item->product?->is_perishable)
                                            <span class="block text-xs text-stock-low">Perecedero: se reembolsa pero no vuelve a la venta</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-right">{{ Number::format($item->quantity, maxPrecision: 3, locale: 'es') }} {{ $item->unit_type->suffix() }}</td>
                                    <td class="py-2 text-right">{{ Number::format($refundable, maxPrecision: 3, locale: 'es') }} {{ $item->unit_type->suffix() }}</td>
                                    <td class="py-2 text-right">
                                        <input type="number" name="quantities[{{ $item->id }}]" min="0" max="{{ $refundable }}"
                                               step="{{ $item->unit_type === \App\Enums\UnitType::Weight ? '0.005' : '1' }}"
                                               value="{{ old("quantities.{$item->id}", 0) }}" @disabled($refundable <= 0)
                                               class="w-24 rounded-control border border-line-strong px-2 py-1 text-right" aria-label="Cantidad a devolver de {{ $item->product_name }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @error('quantities') <p class="text-[13px] font-semibold text-red-700">{{ $message }}</p> @enderror

                    <x-input name="reason" label="Motivo de la devolución" required placeholder="Ej.: producto en mal estado" />
                    <x-button variant="danger">Registrar devolución y reembolsar</x-button>
                </form>
            @endif
        </x-card>
    </div>
</x-layouts.app>
