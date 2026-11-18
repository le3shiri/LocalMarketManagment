<x-layouts.app :title="'Lotes · '.$product->name">
    <x-warehouse-tabs />

    <div class="mb-4">
        <a href="{{ route('warehouse.stock.index') }}" class="inline-flex min-h-11 items-center text-[15px] font-bold text-action hover:underline">← Inventario</a>
        <h1 class="mt-1 text-[28px] font-extrabold tracking-tight text-ink">Lotes de {{ $product->name }}</h1>
        <p class="text-sm text-ink-muted">Existencias: {{ $product->formatQuantity($product->stock->on_hand) }} · Las salidas consumen primero el lote que caduca antes (FEFO).</p>
    </div>

    <div class="overflow-x-auto rounded-card bg-card shadow-card">
        <table class="min-w-full divide-y divide-line text-sm">
            <thead class="bg-sunken text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted">
                <tr>
                    <th scope="col" class="px-4 py-3">Lote</th>
                    <th scope="col" class="px-4 py-3">Caducidad</th>
                    <th scope="col" class="px-4 py-3 text-right">Recibido</th>
                    <th scope="col" class="px-4 py-3 text-right">Queda</th>
                    <th scope="col" class="px-4 py-3">Estado</th>
                    <th scope="col" class="px-4 py-3">Recepción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($batches as $batch)
                    <tr @class(['opacity-50' => $batch->quantity_remaining <= 0])>
                        <td class="px-4 py-3 font-mono">{{ $batch->lot_code }}</td>
                        <td class="px-4 py-3">
                            @if ($batch->expires_at)
                                {{ $batch->expires_at->format('d/m/Y') }}
                                <span @class([
                                    'ml-1 text-xs',
                                    'text-red-700 font-medium' => $batch->days_left < 0,
                                    'text-stock-low' => $batch->days_left >= 0 && $batch->days_left <= 3,
                                    'text-ink-muted' => $batch->days_left > 3,
                                ])>
                                    {{ $batch->days_left < 0 ? 'caducado' : ($batch->days_left === 0 ? 'hoy' : "en {$batch->days_left} días") }}
                                </span>
                            @else
                                <span class="text-ink-muted">Sin caducidad</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">{{ $product->formatQuantity($batch->quantity_received) }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ $product->formatQuantity($batch->quantity_remaining) }}</td>
                        <td class="px-4 py-3 text-xs">{{ ['active' => 'Activo', 'expired' => 'Caducado', 'withdrawn' => 'Retirado'][$batch->status] ?? $batch->status }}</td>
                        <td class="px-4 py-3 text-xs text-ink-muted">{{ $batch->created_at->format('d/m/Y') }} · {{ $batch->receiver?->name }} @if ($batch->reference) · {{ $batch->reference }} @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-ink-muted">Este producto todavía no tiene lotes registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
