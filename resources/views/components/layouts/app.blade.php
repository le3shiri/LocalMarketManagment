<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-page font-sans text-ink antialiased">
    <a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-control focus:bg-card focus:px-4 focus:py-3 focus:font-bold">Saltar al contenido</a>
    <header class="border-b border-line bg-card">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 text-lg font-bold tracking-tight text-ink">
                <span class="grid h-9 w-9 place-items-center rounded-[10px] bg-ink text-sm font-bold text-on-action" aria-hidden="true">LM</span>
                {{ config('app.name') }}
            </a>

            <nav class="flex flex-wrap items-center gap-1 text-[15px]" aria-label="Principal">
                @if (Route::has('catalog.index'))
                    <x-nav-link :href="route('catalog.index')" :active="request()->routeIs('catalog.*')">Catálogo</x-nav-link>
                @endif

                @auth
                    @foreach (\App\Support\Navigation::for(auth()->user()) as $group)
                        <x-nav-group :group="$group" />
                    @endforeach

                    <span class="mx-2 h-6 w-px bg-line" aria-hidden="true"></span>
                    @if (Route::has('notifications.index'))
                        @php($unread = auth()->user()->unreadNotifications()->count())
                        <a href="{{ route('notifications.index') }}" class="relative grid h-11 w-11 place-items-center rounded-control text-ink hover:bg-sunken"
                           title="Avisos" aria-label="Avisos{{ $unread ? " ({$unread} sin leer)" : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                            @if ($unread)
                                <span class="absolute right-1 top-1 grid min-w-5 place-items-center rounded-full bg-action px-1.5 text-[11px] font-extrabold text-on-action ring-2 ring-card" data-unread>{{ $unread }}</span>
                            @endif
                        </a>
                    @endif
                    @if (Route::has('profile.edit'))
                        <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">{{ auth()->user()->name }}</x-nav-link>
                    @else
                        <span class="px-3 py-2 text-ink-soft">{{ auth()->user()->name }}</span>
                    @endif
                    @if (Route::has('logout'))
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="min-h-11 rounded-control px-3 font-medium text-ink-soft hover:bg-sunken">Cerrar sesión</button>
                        </form>
                    @endif
                @else
                    @if (Route::has('login'))
                        <x-nav-link :href="route('login')" :active="request()->routeIs('login')">Iniciar sesión</x-nav-link>
                    @endif
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center rounded-control bg-ink px-4 font-semibold text-on-action hover:bg-stone-800">Crear cuenta</a>
                    @endif
                @endauth
            </nav>
        </div>
    </header>

    <main id="contenido" class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        @if (session('status'))
            <div class="mb-6 flex items-center gap-2 rounded-control border border-success/25 bg-success-soft px-4 py-3 text-[15px] font-semibold text-success-ink" role="status">
                <svg class="h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-6 rounded-control border border-red-200 bg-red-50 px-4 py-3 text-[15px] font-semibold text-red-800" role="alert">
                {{ session('error') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-7xl px-4 pb-8 text-[13px] text-ink-muted sm:px-6">
        LocalMarket · Tienda de barrio · Barcelona
    </footer>
    @livewireScripts
</body>
</html>
