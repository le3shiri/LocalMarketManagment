<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-stone-50 text-stone-800 antialiased">
    <header class="border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3">
            <a href="{{ url('/') }}" class="flex items-center gap-2 text-lg font-semibold text-emerald-700">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-emerald-600 text-white">LM</span>
                {{ config('app.name') }}
            </a>

            <nav class="flex flex-wrap items-center gap-1 text-sm">
                @if (Route::has('catalog.index'))
                    <x-nav-link :href="route('catalog.index')" :active="request()->routeIs('catalog.*')">Catálogo</x-nav-link>
                @endif

                @auth
                    @foreach (\App\Support\Navigation::for(auth()->user()) as $group)
                        <x-nav-group :group="$group" />
                    @endforeach

                    <span class="mx-2 h-5 w-px bg-stone-200"></span>
                    @if (Route::has('notifications.index'))
                        @php($unread = auth()->user()->unreadNotifications()->count())
                        <a href="{{ route('notifications.index') }}" class="relative rounded-md px-2 py-2 text-stone-600 hover:bg-stone-100"
                           title="Avisos" aria-label="Avisos{{ $unread ? " ({$unread} sin leer)" : '' }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                            @if ($unread)
                                <span class="absolute -right-0.5 -top-0.5 rounded-full bg-red-500 px-1.5 text-[10px] font-semibold text-white" data-unread>{{ $unread }}</span>
                            @endif
                        </a>
                    @endif
                    @if (Route::has('profile.edit'))
                        <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">{{ auth()->user()->name }}</x-nav-link>
                    @else
                        <span class="px-3 py-2 text-stone-600">{{ auth()->user()->name }}</span>
                    @endif
                    @if (Route::has('logout'))
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-md px-3 py-2 text-stone-600 hover:bg-stone-100">Cerrar sesión</button>
                        </form>
                    @endif
                @else
                    @if (Route::has('login'))
                        <x-nav-link :href="route('login')" :active="request()->routeIs('login')">Iniciar sesión</x-nav-link>
                    @endif
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="rounded-md bg-emerald-600 px-3 py-2 font-medium text-white hover:bg-emerald-700">Crear cuenta</a>
                    @endif
                @endauth
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                {{ session('error') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-6xl px-4 pb-8 text-xs text-stone-400">
        LocalMarket · Tienda de barrio · Barcelona
    </footer>
    @livewireScripts
</body>
</html>
