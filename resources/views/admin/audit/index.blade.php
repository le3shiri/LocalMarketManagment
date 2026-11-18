<x-layouts.app title="Historial de operaciones">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-stone-900">Historial de operaciones</h1>
        <p class="text-sm text-stone-500">Quién hizo qué, cuándo y desde dónde.</p>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <select name="user" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Usuario">
            <option value="">Todos los usuarios</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected(request('user') == $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        <select name="action" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Tipo de acción">
            <option value="">Todas las acciones</option>
            @foreach ($actions as $value => $label)
                <option value="{{ $value }}" @selected(request('action') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Desde">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Hasta">
        <x-button variant="secondary">Filtrar</x-button>
    </form>

    <div class="overflow-x-auto rounded-xl border border-stone-200 bg-white">
        <table class="min-w-full divide-y divide-stone-200 text-sm">
            <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                <tr>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Usuario</th>
                    <th class="px-4 py-3">Acción</th>
                    <th class="px-4 py-3">Detalle</th>
                    <th class="px-4 py-3">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100 align-top">
                @forelse ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 text-stone-500" title="{{ $log->created_at }}">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $log->user?->name ?? 'Sistema' }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-red-50 text-red-700' => in_array($log->action, ['deleted', 'login_failed']),
                                'bg-sky-50 text-sky-700' => in_array($log->action, ['login', 'logout']),
                                'bg-emerald-50 text-emerald-700' => in_array($log->action, ['created', 'order', 'stock']),
                                'bg-amber-50 text-amber-700' => in_array($log->action, ['updated', 'permissions']),
                            ])>{{ $log->actionLabel() }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <p>{{ $log->description }}</p>
                            @if ($log->changes)
                                <details class="mt-1 text-xs text-stone-500">
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
                        <td class="px-4 py-3 font-mono text-xs text-stone-400">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-stone-500">No hay operaciones con estos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</x-layouts.app>
