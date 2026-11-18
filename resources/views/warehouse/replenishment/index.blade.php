@use('App\Http\Controllers\Warehouse\ReplenishmentController')
<x-layouts.app title="Reposición">
    <x-warehouse-tabs />

    <div class="mb-4">
        <h1 class="text-[28px] font-extrabold tracking-tight text-ink">Reposición</h1>
        <p class="mt-1 text-[15px] text-ink-muted">Productos por debajo de su mínimo, del más urgente al menos. Pedido sugerido: hasta el doble del mínimo.</p>
    </div>

    <div class="overflow-x-auto rounded-card bg-card shadow-card">
        <table class="min-w-full divide-y divide-line text-sm">
            <thead class="bg-sunken text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted">
                <tr>
                    <th scope="col" class="px-4 py-3">Producto</th>
                    <th scope="col" class="px-4 py-3 text-right">Existencias</th>
                    <th scope="col" class="px-4 py-3 text-right">Mínimo</th>
                    <th scope="col" class="px-4 py-3 text-right">Faltan</th>
                    <th scope="col" class="px-4 py-3 text-right">Pedido sugerido</th>
                    <th scope="col" class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($stocks as $stock)
                    @php($product = $stock->product)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $product->name }}</p>
                            <p class="text-xs text-ink-muted">{{ $product->category->name }} · <span class="font-mono">{{ $product->sku }}</span></p>
                        </td>
                        <td @class(['px-4 py-3 text-right font-medium', 'text-red-700' => $stock->on_hand <= 0])>{{ $product->formatQuantity($stock->on_hand) }}</td>
                        <td class="px-4 py-3 text-right">{{ $product->formatQuantity($stock->min_alert) }}</td>
                        <td class="px-4 py-3 text-right text-stock-low">{{ $product->formatQuantity($stock->min_alert - $stock->on_hand) }}</td>
                        <td class="px-4 py-3 text-right font-semibold" data-suggested="{{ $product->id }}">{{ $product->formatQuantity(ReplenishmentController::suggestedOrder($stock)) }}</td>
                        <td class="px-4 py-3 text-right">
                            @can('stock.entry')
                                <a href="{{ route('warehouse.entries.create', ['product' => $product->id]) }}" class="whitespace-nowrap text-sm font-medium text-action hover:underline">Registrar entrada</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-ink-muted">Todo en orden: ningún producto está por debajo de su mínimo.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
