<x-layouts.app title="Historial de operaciones">
    <div class="mb-6">
        <h1 class="text-[28px] font-extrabold tracking-tight text-ink">Historial de operaciones</h1>
        <p class="mt-1 text-[15px] text-ink-muted">Quién hizo qué, cuándo y desde dónde.</p>
        @if ($integrity['valid'])
            <p class="mt-2 inline-flex items-center gap-1 rounded-full bg-success-soft px-3 py-1 text-xs font-bold text-success-ink" data-integrity="ok">
                ✓ Historial íntegro: {{ $integrity['checked'] }} entradas encadenadas verificadas
            </p>
        @else
            <p class="mt-2 inline-flex items-center gap-1 rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800" data-integrity="broken" role="alert">
                ⚠ Historial manipulado: la cadena se rompe en la entrada #{{ $integrity['broken_at'] }}
            </p>
        @endif
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <select name="user" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Usuario">
            <option value="">Todos los usuarios</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected(request('user') == $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        <select name="action" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Tipo de acción">
            <option value="">Todas las acciones</option>
            @foreach ($actions as $value => $label)
                <option value="{{ $value }}" @selected(request('action') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Desde">
        <input type="date" name="to" value="{{ request('to') }}" class="min-h-11 rounded-control border border-line-strong bg-card px-3 text-[15px]" aria-label="Hasta">
        <label class="flex items-center gap-2 px-2 text-sm text-ink-soft">
            <input type="checkbox" name="sensibles" value="1" class="h-5 w-5 rounded border-line-strong accent-red-700" @checked($sensitiveOnly)>
            Solo operaciones sensibles
        </label>
        <x-button variant="secondary">Filtrar</x-button>
    </form>

    <div class="overflow-x-auto rounded-card bg-card shadow-card">
        <table class="min-w-full divide-y divide-line text-sm">
            <thead class="bg-sunken text-left text-xs font-bold uppercase tracking-[0.06em] text-ink-muted">
                <tr>
                    <th scope="col" class="px-4 py-3">Fecha</th>
                    <th scope="col" class="px-4 py-3">Usuario</th>
                    <th scope="col" class="px-4 py-3">Acción</th>
                    <th scope="col" class="px-4 py-3">Detalle</th>
                    <th scope="col" class="px-4 py-3">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line align-top">
                @forelse ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 text-ink-muted" title="{{ $log->created_at }}">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $log->user?->name ?? 'Sistema' }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-red-50 text-red-700' => in_array($log->action, ['deleted', 'login_failed']),
                                'bg-action-soft text-action' => in_array($log->action, ['login', 'logout']),
                                'bg-success-soft text-success-ink' => in_array($log->action, ['created', 'order', 'stock']),
                                'bg-warn-soft text-stock-low' => in_array($log->action, ['updated', 'permissions']),
                            ])>{{ $log->actionLabel() }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <p>
                                @if ($log->is_sensitive)
                                    <span class="mr-1 rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-red-700" title="Operación sensible">Sensible</span>
                                @endif
                                {{ $log->description }}
                            </p>
                            @if ($log->changes)
                                <details class="mt-1 text-xs text-ink-muted">
                                    <summary class="cursor-pointer">Ver cambios</summary>
                                    <ul class="mt-1 space-y-0.5">
                                        @foreach ($log->changes as $field => $change)
                                            <li>
                                                <span class="font-mono">{{ $field }}</span>:
                                                @if (is_array($change) && array_key_exists('antes', $change))
                                                    {{ json_encode($change['antes'], JSON_UNESCAPED_UNICODE) }} → {{ json_encode($change['después'], JSON_UNESCAPED_UNICODE) }}
                                                @else
                                                    {{ is_array($change) ? implode(', ', $change) ?: '—' : $change }}
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-ink-muted">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-ink-muted">No hay operaciones con estos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</x-layouts.app>
