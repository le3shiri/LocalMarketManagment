<x-layouts.app title="Avisos">
    <x-page-header title="Avisos" subtitle="Pedidos confirmados, listos o cancelados, y alertas del almacén.">
        @if (auth()->user()->unreadNotifications()->exists())
            <x-slot:actions>
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button class="inline-flex min-h-11 items-center rounded-control px-3 text-sm font-bold text-action hover:bg-action-soft">Marcar todos como leídos</button>
                </form>
            </x-slot:actions>
        @endif
    </x-page-header>

    @if ($notifications->isEmpty())
        <section class="mx-auto max-w-xl rounded-shelf border border-line bg-card px-8 py-12 text-center">
            <div class="mx-auto grid h-16 w-16 place-items-center rounded-[20px] bg-sunken" aria-hidden="true">
                <svg class="h-7 w-7 text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
            </div>
            <h2 class="mt-5 text-2xl font-extrabold tracking-tight">No tienes avisos.</h2>
            <p class="mt-2 text-base text-ink-soft">Te avisaremos aquí y por email cuando algo cambie.</p>
        </section>
    @else
        <ul class="divide-y divide-line overflow-hidden rounded-card bg-card shadow-card">
            @foreach ($notifications as $notification)
                <li>
                    <a href="{{ route('notifications.show', $notification->id) }}" @class([
                        'flex items-start gap-3 p-4 hover:bg-page',
                        'bg-action-soft' => ! $notification->read_at,
                    ])>
                        <span @class(['mt-2 h-2 w-2 shrink-0 rounded-full', 'bg-action' => ! $notification->read_at, 'bg-transparent' => $notification->read_at]) aria-hidden="true"></span>
                        <span class="flex-1">
                            <span @class(['block text-[15px]', 'font-extrabold text-ink' => ! $notification->read_at, 'font-semibold text-ink-soft' => $notification->read_at])>
                                @unless ($notification->read_at)<span class="sr-only">Sin leer: </span>@endunless{{ $notification->data['title'] ?? 'Aviso' }}
                            </span>
                            <span class="block text-sm text-ink-muted">{{ $notification->data['body'] ?? '' }}</span>
                        </span>
                        <span class="whitespace-nowrap text-[13px] text-ink-muted">{{ $notification->created_at->diffForHumans() }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="mt-5">{{ $notifications->links() }}</div>
    @endif
</x-layouts.app>
