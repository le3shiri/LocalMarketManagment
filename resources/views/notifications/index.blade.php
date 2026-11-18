<x-layouts.app title="Avisos">
    <div class="mb-6 flex items-end justify-between gap-4">
        <h1 class="text-[28px] font-extrabold tracking-tight text-ink">Avisos</h1>
        @if (auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="text-sm text-action hover:underline">Marcar todos como leídos</button>
            </form>
        @endif
    </div>

    <ul class="divide-y divide-line rounded-card border border-line bg-white">
        @forelse ($notifications as $notification)
            <li>
                <a href="{{ route('notifications.show', $notification->id) }}" @class([
                    'flex items-start gap-3 p-4 hover:bg-page',
                    'bg-action-soft' => ! $notification->read_at,
                ])>
                    <span @class(['mt-1.5 h-2 w-2 shrink-0 rounded-full', 'bg-action' => ! $notification->read_at, 'bg-transparent' => $notification->read_at])></span>
                    <span class="flex-1">
                        <span @class(['block text-sm', 'font-semibold text-ink' => ! $notification->read_at, 'text-ink-soft' => $notification->read_at])>{{ $notification->data['title'] ?? 'Aviso' }}</span>
                        <span class="block text-sm text-ink-muted">{{ $notification->data['body'] ?? '' }}</span>
                    </span>
                    <span class="whitespace-nowrap text-xs text-ink-muted">{{ $notification->created_at->diffForHumans() }}</span>
                </a>
            </li>
        @empty
            <li class="p-10 text-center text-ink-muted">No tienes avisos.</li>
        @endforelse
    </ul>
    <div class="mt-4">{{ $notifications->links() }}</div>
</x-layouts.app>
