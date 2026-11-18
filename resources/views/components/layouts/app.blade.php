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
                    @if (Route::has('cart.index') && auth()->user()->can('cart.use'))
                        <x-nav-link :href="route('cart.index')" :active="request()->routeIs('cart.*', 'checkout.*')">
                            Carrito
                            @php($cartCount = auth()->user()->cartItems()->count())
                            @if ($cartCount > 0)
                                <span class="ml-1 rounded-full bg-emerald-600 px-1.5 text-xs text-white">{{ $cartCount }}</span>
                            @endif
                        </x-nav-link>
                    @endif
                    @if (Route::has('admin.products.index') && auth()->user()->can('products.manage'))
                        <x-nav-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')">Productos</x-nav-link>
                    @endif
                    @if (Route::has('admin.categories.index') && auth()->user()->can('categories.manage'))
                        <x-nav-link :href="route('admin.categories.index')" :active="request()->routeIs('admin.categories.*')">Categorías</x-nav-link>
                    @endif
                    @if (Route::has('warehouse.stock.index') && auth()->user()->can('stock.view'))
                        <x-nav-link :href="route('warehouse.stock.index')" :active="request()->routeIs('warehouse.*')">Almacén</x-nav-link>
                    @endif
                    @if (Route::has('admin.users.index') && auth()->user()->can('users.manage'))
                        <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">Usuarios</x-nav-link>
                    @endif
                    @if (Route::has('admin.roles.index') && auth()->user()->can('roles.manage'))
                        <x-nav-link :href="route('admin.roles.index')" :active="request()->routeIs('admin.roles.*')">Roles</x-nav-link>
                    @endif
                    @if (Route::has('admin.audit.index') && auth()->user()->can('audit.view'))
                        <x-nav-link :href="route('admin.audit.index')" :active="request()->routeIs('admin.audit.*')">Historial</x-nav-link>
                    @endif

                    <span class="mx-2 h-5 w-px bg-stone-200"></span>
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
