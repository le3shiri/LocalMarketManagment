<x-layouts.app :title="'Lotes · '.$product->name">
    <x-warehouse-tabs />

    <div class="mb-4">
        <a href="{{ route('warehouse.stock.index') }}" class="text-sm text-stone-500 hover:underline">← Inventario</a>
        <h1 class="mt-1 text-2xl font-bold text-stone-900">Lotes de {{ $product->name }}</h1>
        <p class="text-sm text-stone-500">Existencias: {{ $product->formatQuantity($product->stock->on_hand) }} · Las salidas consumen primero el lote que caduca antes (FEFO).</p>
    </div>

    <div class="overflow-x-auto rounded-xl border border-stone-200 bg-white">
        <table class="min-w-full divide-y divide-stone-200 text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                <tr>
                    <th class="px-4 py-3">Lote</th>
                    <th class="px-4 py-3">Caducidad</th>
                    <th class="px-4 py-3 text-right">Recibido</th>
                    <th class="px-4 py-3 text-right">Queda</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Recepción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($batches as $batch)
                    <tr @class(['opacity-50' => $batch->quantity_remaining <= 0])>
                        <td class="px-4 py-3 font-mono">{{ $batch->lot_code }}</td>
                        <td class="px-4 py-3">
                            @if ($batch->expires_at)
                                {{ $batch->expires_at->format('d/m/Y') }}
                                <span @class([
                                    'ml-1 text-xs',
                                    'text-red-600 font-medium' => $batch->days_left < 0,
                                    'text-amber-700' => $batch->days_left >= 0 && $batch->days_left <= 3,
                                    'text-stone-400' => $batch->days_left > 3,
                                ])>
                                    {{ $batch->days_left < 0 ? 'caducado' : ($batch->days_left === 0 ? 'hoy' : "en {$batch->days_left} días") }}
                                </span>
                            @else
                                <span class="text-stone-400">Sin caducidad</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">{{ $product->formatQuantity($batch->quantity_received) }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ $product->formatQuantity($batch->quantity_remaining) }}</td>
                        <td class="px-4 py-3 text-xs">{{ ['active' => 'Activo', 'expired' => 'Caducado', 'withdrawn' => 'Retirado'][$batch->status] ?? $batch->status }}</td>
                        <td class="px-4 py-3 text-xs text-stone-500">{{ $batch->created_at->format('d/m/Y') }} · {{ $batch->receiver?->name }} @if ($batch->reference) · {{ $batch->reference }} @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-stone-500">Este producto todavía no tiene lotes registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
