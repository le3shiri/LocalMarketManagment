<x-layouts.app title="Copias de seguridad">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-stone-900">Copias de seguridad</h1>
            <p class="text-sm text-stone-500">Copia automática cada día a las 02:00. Se guardan las {{ $keep }} más recientes.</p>
        </div>
        <form method="POST" action="{{ route('admin.backups.store') }}">
            @csrf
            <x-button>Crear copia ahora</x-button>
        </form>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="overflow-x-auto rounded-xl border border-stone-200 bg-white lg:col-span-2">
            <table class="min-w-full divide-y divide-stone-200 text-sm">
                <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                    <tr><th class="px-4 py-3">Copia</th><th class="px-4 py-3">Fecha</th><th class="px-4 py-3 text-right">Tamaño</th><th class="px-4 py-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($backups as $backup)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $backup['name'] }}</td>
                            <td class="px-4 py-3">{{ $backup['created_at']->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-right">{{ $service->humanSize($backup['size']) }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.backups.download', $backup['name']) }}" class="text-sm font-medium text-emerald-700 hover:underline">Descargar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-stone-500">Todavía no hay copias. Crea la primera ahora.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-card title="Restaurar una copia" class="h-fit">
            <p class="text-sm text-stone-600">
                Restaurar sustituye <strong>todos</strong> los datos actuales, así que solo se hace desde la consola del servidor,
                y siempre se crea antes una copia de los datos actuales:
            </p>
            <pre class="mt-3 overflow-x-auto rounded-lg bg-stone-900 p-3 text-xs text-stone-100">php artisan backup:restore</pre>
            <p class="mt-3 text-xs text-stone-500">El procedimiento completo está en <code>docs/BACKUP.md</code>. Guarda también una copia fuera del servidor (descárgala) al menos una vez por semana.</p>
        </x-card>
    </div>
</x-layouts.app>
