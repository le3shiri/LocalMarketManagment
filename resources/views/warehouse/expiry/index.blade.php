<x-layouts.app title="Caducidades">
    <x-warehouse-tabs />

    <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-stone-900">Próximos a caducar</h1>
            <p class="text-sm text-stone-500">Lotes con existencias que caducan en los próximos {{ $days }} días. Lo primero de la lista es lo primero que debe salir.</p>
        </div>
        <form method="GET" class="flex items-end gap-2">
            <label class="text-sm text-stone-600">Ver
                <select name="dias" onchange="this.form.submit()" class="ml-1 rounded-lg border border-stone-300 px-2 py-1.5 text-sm">
                    @foreach ([1, 3, 7, 14, 30] as $option)
                        <option value="{{ $option }}" @selected($days === $option)>{{ $option }} {{ $option === 1 ? 'día' : 'días' }}</option>
                    @endforeach
                </select>
            </label>
        </form>
    </div>

    <div class="overflow-x-auto rounded-xl border border-stone-200 bg-white">
        <table class="min-w-full divide-y divide-stone-200 text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                <tr>
                    <th class="px-4 py-3">Caduca</th>
                    <th class="px-4 py-3">Producto</th>
                    <th class="px-4 py-3">Lote</th>
                    <th class="px-4 py-3 text-right">Quedan</th>
                    <th class="px-4 py-3">Ubicación</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($batches as $batch)
                    <tr @class(['bg-red-50' => $batch->days_left === 0, 'bg-amber-50' => $batch->days_left === 1])>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span @class([
                                'rounded-full px-2 py-0.5 text-xs font-semibold',
                                'bg-red-600 text-white' => $batch->days_left === 0,
                                'bg-amber-500 text-white' => $batch->days_left === 1,
                                'bg-amber-100 text-amber-800' => $batch->days_left > 1,
                            ]) data-days="{{ $batch->days_left }}">{{ $batch->days_left === 0 ? 'Hoy' : ($batch->days_left === 1 ? 'Mañana' : "En {$batch->days_left} días") }}</span>
                            <span class="ml-1 text-xs text-stone-500">{{ $batch->expires_at->format('d/m') }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('warehouse.batches.index', $batch->product) }}" class="font-medium hover:underline">{{ $batch->product->name }}</a>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $batch->lot_code }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ $batch->product->formatQuantity($batch->quantity_remaining) }}</td>
                        <td class="px-4 py-3 text-stone-500">{{ $batch->product->location ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-stone-500">Ningún lote caduca en los próximos {{ $days }} días.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
