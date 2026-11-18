<x-layouts.app title="Avisos">
    <div class="mb-6 flex items-end justify-between gap-4">
        <h1 class="text-2xl font-bold text-stone-900">Avisos</h1>
        @if (auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="text-sm text-emerald-700 hover:underline">Marcar todos como leídos</button>
            </form>
        @endif
    </div>

    <ul class="divide-y divide-stone-100 rounded-xl border border-stone-200 bg-white">
        @forelse ($notifications as $notification)
            <li>
                <a href="{{ route('notifications.show', $notification->id) }}" @class([
                    'flex items-start gap-3 p-4 hover:bg-stone-50',
                    'bg-emerald-50/50' => ! $notification->read_at,
                ])>
                    <span @class(['mt-1.5 h-2 w-2 shrink-0 rounded-full', 'bg-emerald-500' => ! $notification->read_at, 'bg-transparent' => $notification->read_at])></span>
                    <span class="flex-1">
                        <span @class(['block text-sm', 'font-semibold text-stone-900' => ! $notification->read_at, 'text-stone-700' => $notification->read_at])>{{ $notification->data['title'] ?? 'Aviso' }}</span>
                        <span class="block text-sm text-stone-500">{{ $notification->data['body'] ?? '' }}</span>
                    </span>
                    <span class="whitespace-nowrap text-xs text-stone-400">{{ $notification->created_at->diffForHumans() }}</span>
                </a>
            </li>
        @empty
            <li class="p-10 text-center text-stone-500">No tienes avisos.</li>
        @endforelse
    </ul>
    <div class="mt-4">{{ $notifications->links() }}</div>
</x-layouts.app>
