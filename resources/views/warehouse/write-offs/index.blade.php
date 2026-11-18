@use('Illuminate\Support\Number')
<x-layouts.app title="Mermas">
    <x-warehouse-tabs />

    <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-[28px] font-extrabold tracking-tight text-ink">Mermas</h1>
            <p class="mt-1 text-[15px] text-ink-muted">Del {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }} · valoradas a precio de venta.</p>
        </div>
        <form method="GET" class="flex flex-wrap items-end gap-2">
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Desde">
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Hasta">
            <select name="type" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Tipo">
                <option value="">Todos los tipos</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(($filters['type'] ?? '') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
            <x-button variant="secondary">Filtrar</x-button>
            <x-csv-link :href="route('warehouse.write-offs.export', request()->query())" />
        </form>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <div class="rounded-card bg-card p-5 shadow-card">
            <p class="text-[13px] font-bold uppercase tracking-[0.06em] text-ink-muted">Total mermas</p>
            <p class="mt-1 text-2xl font-bold text-red-700" data-writeoff-total>{{ Number::currency($total, 'EUR', 'es') }}</p>
        </div>
        @foreach ($types as $type)
            @php($row = $byType->get($type->value))
            <div class="rounded-card bg-card p-5 shadow-card" data-type="{{ $type->value }}">
                <p class="text-[13px] font-bold uppercase tracking-[0.06em] text-ink-muted">{{ $type->label() }}</p>
                <p class="mt-1 text-2xl font-extrabold tracking-tight">{{ Number::currency((float) ($row->value ?? 0), 'EUR', 'es') }}</p>
                <p class="text-xs text-ink-muted">{{ $row->records ?? 0 }} registros</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-5">
        @can('stock.exit')
            <x-card title="Registrar merma" class="lg:col-span-2">
                <form method="POST" action="{{ route('warehouse.write-offs.store') }}" class="space-y-4" x-data="{ product: '{{ old('product_id') }}' }">
                    @csrf
                    <div class="space-y-1">
                        <label for="product_id" class="block text-sm font-semibold text-ink-soft">Producto</label>
                        <select id="product_id" name="product_id" x-model="product" required class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
                            <option value="">Elige un producto…</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} · tienda {{ $product->formatQuantity($product->stock->store_qty) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label for="stock_batch_id" class="block text-sm font-semibold text-ink-soft">Lote (opcional)</label>
                        <select id="stock_batch_id" name="stock_batch_id" class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
                            <option value="">El que caduca antes (automático)</option>
                            @foreach ($products as $product)
                                @foreach ($product->batches as $batch)
                                    <option value="{{ $batch->id }}" x-show="product == '{{ $product->id }}'" @selected(old('stock_batch_id') == $batch->id)>
                                        {{ $batch->lot_code }} · quedan {{ $product->formatQuantity($batch->quantity_remaining) }}{{ $batch->expires_at ? ' · cad. '.$batch->expires_at->format('d/m') : '' }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label for="type" class="block text-sm font-semibold text-ink-soft">Tipo</label>
                        <select id="type" name="type" class="block w-full min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]">
                            @foreach ($types as $type)
                                <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <fieldset class="flex gap-4 text-sm">
                        <legend class="mb-1 block text-sm font-medium text-ink-soft">Sale de</legend>
                        <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 pr-3 font-semibold"><input type="radio" name="channel" value="store" @checked(old('channel', 'store') === 'store') class="accent-action"> Tienda</label>
                        <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 pr-3 font-semibold"><input type="radio" name="channel" value="online" @checked(old('channel') === 'online') class="accent-action"> Online (sin reservar)</label>
                    </fieldset>
                    <x-input name="quantity" label="Cantidad" inputmode="decimal" required />
                    <x-input name="notes" label="Observaciones" placeholder="Ej.: caja aplastada en la descarga" />
                    <x-button variant="danger" class="w-full">Registrar merma</x-button>
                </form>
            </x-card>
        @endcan

        <div class="overflow-x-auto rounded-card bg-card shadow-card lg:col-span-3">
            <table class="min-w-full divide-y divide-line text-sm">
                <thead class="bg-sunken text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted">
                    <tr><th scope="col" class="px-4 py-3">Fecha</th><th scope="col" class="px-4 py-3">Producto</th><th scope="col" class="px-4 py-3">Tipo</th><th scope="col" class="px-4 py-3 text-right">Cantidad</th><th scope="col" class="px-4 py-3 text-right">Valor</th></tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($writeOffs as $writeOff)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-2 text-ink-muted">{{ $writeOff->created_at->format('d/m H:i') }}</td>
                            <td class="px-4 py-2">
                                {{ $writeOff->product->name }}
                                <span class="block text-xs text-ink-muted">{{ $writeOff->batch ? 'Lote '.$writeOff->batch->lot_code.' · ' : '' }}{{ $writeOff->user?->name }}{{ $writeOff->notes ? ' · '.$writeOff->notes : '' }}</span>
                            </td>
                            <td class="px-4 py-2">{{ $writeOff->type->label() }}</td>
                            <td class="px-4 py-2 text-right">{{ $writeOff->product->formatQuantity($writeOff->quantity) }}</td>
                            <td class="px-4 py-2 text-right text-red-700">{{ Number::currency((float) $writeOff->value, 'EUR', 'es') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-ink-muted">Sin mermas en este periodo.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $writeOffs->links() }}</div>
        </div>
    </div>
</x-layouts.app>
