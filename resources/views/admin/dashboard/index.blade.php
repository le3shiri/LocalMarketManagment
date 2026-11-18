@use('Illuminate\Support\Number')
@use('App\Enums\OrderStatus')
<x-layouts.app title="Panel">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-[28px] font-extrabold tracking-tight text-ink">Panel de control</h1>
            <p class="mt-1 text-[15px] text-ink-muted">{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM, HH:mm')) }}</p>
        </div>
        @if (Route::has('admin.reports.index'))
            <a href="{{ route('admin.reports.index') }}" class="rounded-control border border-line-strong bg-card px-4 py-2 text-sm font-medium hover:bg-page">Informes</a>
        @endif
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-card bg-card p-5 shadow-card">
            <p class="text-[13px] font-bold uppercase tracking-[0.06em] text-ink-muted">Ventas de hoy</p>
            <p class="mt-1 text-3xl font-extrabold tracking-tight text-ink" data-sales-today>{{ Number::currency($sales['today'], 'EUR', 'es') }}</p>
            <p class="text-xs text-ink-muted">
                @if (! is_null($sales['change']))
                    <span @class(['font-bold', 'text-success-ink' => $sales['change'] >= 0, 'text-red-700' => $sales['change'] < 0]) data-sales-change>
                        {{ $sales['change'] >= 0 ? '▲' : '▼' }} {{ Number::format(abs($sales['change']), maxPrecision: 1, locale: 'es') }} %
                    </span>
                    vs. el mismo día de la semana pasada ({{ Number::currency($sales['lastWeek'], 'EUR', 'es') }})
                @else
                    Sin ventas el mismo día de la semana pasada
                @endif
            </p>
        </div>
        <div class="rounded-card bg-card p-5 shadow-card">
            <p class="text-[13px] font-bold uppercase tracking-[0.06em] text-ink-muted">Pedidos de hoy</p>
            <p class="mt-1 text-3xl font-extrabold tracking-tight text-ink" data-orders-today>{{ $orders['total'] }}</p>
            <p class="text-xs text-ink-muted">
                Web {{ $orders['byChannel']['web'] ?? 0 }} · Teléfono {{ $orders['byChannel']['phone'] ?? 0 }} · WhatsApp {{ $orders['byChannel']['whatsapp'] ?? 0 }}
            </p>
        </div>
        <a href="{{ Route::has('staff.orders.index') ? route('staff.orders.index', ['estado' => 'pending']) : '#' }}" @class([
            'rounded-card border p-4 transition hover:shadow-sm',
            'border-stock-low/40 bg-warn-soft' => $pending > 0,
            'border-line bg-card' => $pending === 0,
        ])>
            <p class="text-[13px] font-bold uppercase tracking-[0.06em] text-ink-muted">Pendientes de preparar</p>
            <p class="mt-1 text-3xl font-extrabold tracking-tight text-ink" data-pending>{{ $pending }}</p>
            <p class="text-xs text-ink-muted">Con franja de hoy o atrasados</p>
        </a>
        <div class="rounded-card bg-card p-5 shadow-card">
            <p class="text-[13px] font-bold uppercase tracking-[0.06em] text-ink-muted">Estado de los pedidos de hoy</p>
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
                              class="{{ $loop->last ? 'fill-action' : 'fill-action/35' }}"></rect>
                        <text x="{{ $x + 17 }}" y="205" text-anchor="middle" class="fill-ink-muted text-[11px]">{{ \Carbon\Carbon::parse($day)->format('d/m') }}</text>
                        @if ($amount > 0)
                            <text x="{{ $x + 17 }}" y="{{ 180 - $h }}" text-anchor="middle" class="fill-ink-soft text-[10px]">{{ Number::format($amount, maxPrecision: 0, locale: 'es') }}</text>
                        @endif
                    </g>
                @endforeach
            </svg>
            <p class="mt-2 text-xs text-ink-muted">Total 14 días: {{ Number::currency($daily->sum(), 'EUR', 'es') }} · Caja + pedidos cobrados − devoluciones.</p>
        </x-card>

        <x-card title="Más vendidos (30 días)" class="lg:col-span-2">
            <ol class="space-y-2 text-sm" data-top-products>
                @forelse ($topProducts as $row)
                    <li class="flex items-center justify-between gap-3">
                        <span><span class="mr-2 font-semibold text-ink-muted">{{ $row['rank'] }}</span>{{ $row['name'] }}</span>
                        <span class="whitespace-nowrap text-ink-soft">{{ Number::currency($row['revenue'], 'EUR', 'es') }}</span>
                    </li>
                @empty
                    <li class="text-ink-muted">Todavía no hay ventas en los últimos 30 días.</li>
                @endforelse
            </ol>
            @if (Route::has('admin.reports.top'))
                <a href="{{ route('admin.reports.top') }}" class="mt-3 inline-block text-sm font-medium text-action hover:underline">Ver ranking completo</a>
            @endif
        </x-card>

        <x-card title="Alertas de almacén">
            <ul class="space-y-3 text-sm">
                <li class="flex items-center justify-between">
                    <span>Productos bajo mínimo</span>
                    <a href="{{ route('warehouse.replenishment.index') }}" @class(['rounded-full px-2.5 py-0.5 font-semibold', 'bg-warn-soft text-stock-low' => $alerts['lowStock'], 'bg-sunken text-ink-muted' => ! $alerts['lowStock']]) data-alert="low">{{ $alerts['lowStock'] }}</a>
                </li>
                <li class="flex items-center justify-between">
                    <span>Lotes que caducan pronto</span>
                    <a href="{{ route('warehouse.expiry.index') }}" @class(['rounded-full px-2.5 py-0.5 font-semibold', 'bg-warn-soft text-stock-low' => $alerts['expiringSoon'], 'bg-sunken text-ink-muted' => ! $alerts['expiringSoon']]) data-alert="soon">{{ $alerts['expiringSoon'] }}</a>
                </li>
                <li class="flex items-center justify-between">
                    <span>Caducados pendientes de retirar</span>
                    <a href="{{ route('warehouse.expiry.index') }}" @class(['rounded-full px-2.5 py-0.5 font-semibold', 'bg-red-100 text-red-700' => $alerts['expired'], 'bg-sunken text-ink-muted' => ! $alerts['expired']]) data-alert="expired">{{ $alerts['expired'] }}</a>
                </li>
            </ul>
        </x-card>
    </div>
</x-layouts.app>
