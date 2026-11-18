@use('Illuminate\Support\Number')
@use('App\Enums\OrderStatus')
<x-layouts.app title="Panel">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-stone-900">Panel de control</h1>
            <p class="text-sm text-stone-500">{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM, HH:mm')) }}</p>
        </div>
        @if (Route::has('admin.reports.index'))
            <a href="{{ route('admin.reports.index') }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-medium hover:bg-stone-50">Informes</a>
        @endif
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-stone-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-stone-400">Ventas de hoy</p>
            <p class="mt-1 text-3xl font-bold text-stone-900" data-sales-today>{{ Number::currency($sales['today'], 'EUR', 'es') }}</p>
            <p class="text-xs text-stone-500">
                @if (! is_null($sales['change']))
                    <span @class(['font-medium', 'text-emerald-700' => $sales['change'] >= 0, 'text-red-600' => $sales['change'] < 0]) data-sales-change>
                        {{ $sales['change'] >= 0 ? '▲' : '▼' }} {{ Number::format(abs($sales['change']), maxPrecision: 1, locale: 'es') }} %
                    </span>
                    vs. el mismo día de la semana pasada ({{ Number::currency($sales['lastWeek'], 'EUR', 'es') }})
                @else
                    Sin ventas el mismo día de la semana pasada
                @endif
            </p>
        </div>
        <div class="rounded-xl border border-stone-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-stone-400">Pedidos de hoy</p>
            <p class="mt-1 text-3xl font-bold text-stone-900" data-orders-today>{{ $orders['total'] }}</p>
            <p class="text-xs text-stone-500">
                Web {{ $orders['byChannel']['web'] ?? 0 }} · Teléfono {{ $orders['byChannel']['phone'] ?? 0 }} · WhatsApp {{ $orders['byChannel']['whatsapp'] ?? 0 }}
            </p>
        </div>
        <a href="{{ Route::has('staff.orders.index') ? route('staff.orders.index', ['estado' => 'pending']) : '#' }}" @class([
            'rounded-xl border p-4 transition hover:shadow-sm',
            'border-amber-300 bg-amber-50' => $pending > 0,
            'border-stone-200 bg-white' => $pending === 0,
        ])>
            <p class="text-xs uppercase tracking-wide text-stone-400">Pendientes de preparar</p>
            <p class="mt-1 text-3xl font-bold text-stone-900" data-pending>{{ $pending }}</p>
            <p class="text-xs text-stone-500">Con franja de hoy o atrasados</p>
        </a>
        <div class="rounded-xl border border-stone-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-stone-400">Estado de los pedidos de hoy</p>
            <ul class="mt-2 space-y-1 text-sm">
                @foreach (OrderStatus::cases() as $status)
                    <li class="flex justify-between"><x-order-status :status="$status" /><span>{{ $orders['byStatus'][$status->value] ?? 0 }}</span></li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card title="Ventas de los últimos 14 días" class="lg:col-span-2">
            @php($max = max(1, $daily->max()))
            <svg viewBox="0 0 700 220" class="w-full" role="img" aria-label="Gráfico de barras de ventas diarias">
                @foreach ($daily as $day => $amount)
                    @php($h = round(170 * max(0, $amount) / $max))
                    @php($x = $loop->index * 50 + 8)
                    <g>
                        <title>{{ \Carbon\Carbon::parse($day)->format('d/m') }}: {{ Number::currency($amount, 'EUR', 'es') }}</title>
                        <rect x="{{ $x }}" y="{{ 185 - $h }}" width="34" height="{{ $h }}" rx="4"
                              class="{{ $loop->last ? 'fill-emerald-600' : 'fill-emerald-300' }}"></rect>
                        <text x="{{ $x + 17 }}" y="205" text-anchor="middle" class="fill-stone-500 text-[11px]">{{ \Carbon\Carbon::parse($day)->format('d/m') }}</text>
                        @if ($amount > 0)
                            <text x="{{ $x + 17 }}" y="{{ 180 - $h }}" text-anchor="middle" class="fill-stone-700 text-[10px]">{{ Number::format($amount, maxPrecision: 0, locale: 'es') }}</text>
                        @endif
                    </g>
                @endforeach
            </svg>
            <p class="mt-2 text-xs text-stone-500">Total 14 días: {{ Number::currency($daily->sum(), 'EUR', 'es') }} · Caja + pedidos cobrados − devoluciones.</p>
        </x-card>

        <x-card title="Más vendidos (30 días)" class="lg:col-span-2">
            <ol class="space-y-2 text-sm" data-top-products>
                @forelse ($topProducts as $row)
                    <li class="flex items-center justify-between gap-3">
                        <span><span class="mr-2 font-semibold text-stone-400">{{ $row['rank'] }}</span>{{ $row['name'] }}</span>
                        <span class="whitespace-nowrap text-stone-600">{{ Number::currency($row['revenue'], 'EUR', 'es') }}</span>
                    </li>
                @empty
                    <li class="text-stone-500">Todavía no hay ventas en los últimos 30 días.</li>
                @endforelse
            </ol>
            @if (Route::has('admin.reports.top'))
                <a href="{{ route('admin.reports.top') }}" class="mt-3 inline-block text-sm font-medium text-emerald-700 hover:underline">Ver ranking completo</a>
            @endif
        </x-card>

        <x-card title="Alertas de almacén">
            <ul class="space-y-3 text-sm">
                <li class="flex items-center justify-between">
                    <span>Productos bajo mínimo</span>
                    <a href="{{ route('warehouse.replenishment.index') }}" @class(['rounded-full px-2.5 py-0.5 font-semibold', 'bg-amber-100 text-amber-800' => $alerts['lowStock'], 'bg-stone-100 text-stone-500' => ! $alerts['lowStock']]) data-alert="low">{{ $alerts['lowStock'] }}</a>
                </li>
                <li class="flex items-center justify-between">
                    <span>Lotes que caducan pronto</span>
                    <a href="{{ route('warehouse.expiry.index') }}" @class(['rounded-full px-2.5 py-0.5 font-semibold', 'bg-amber-100 text-amber-800' => $alerts['expiringSoon'], 'bg-stone-100 text-stone-500' => ! $alerts['expiringSoon']]) data-alert="soon">{{ $alerts['expiringSoon'] }}</a>
                </li>
                <li class="flex items-center justify-between">
                    <span>Caducados pendientes de retirar</span>
                    <a href="{{ route('warehouse.expiry.index') }}" @class(['rounded-full px-2.5 py-0.5 font-semibold', 'bg-red-100 text-red-700' => $alerts['expired'], 'bg-stone-100 text-stone-500' => ! $alerts['expired']]) data-alert="expired">{{ $alerts['expired'] }}</a>
                </li>
            </ul>
        </x-card>
    </div>
</x-layouts.app>
