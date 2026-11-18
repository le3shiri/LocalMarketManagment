@props(['group'])

@php($badge = collect($group['items'])->sum('badge'))

@if (count($group['items']) === 1)
    @php($item = $group['items'][0])
    <x-nav-link :href="$item['url']" :active="$item['active']">
        {{ $item['label'] }}
        @if ($item['badge'])
            <span class="ml-1 rounded-full bg-amber-500 px-1.5 text-xs text-white" data-badge>{{ $item['badge'] }}</span>
        @endif
    </x-nav-link>
@else
    {{-- Accessible dropdown: button with aria-expanded, closes on Escape and click outside. --}}
    {{-- alignRight: menus near the right edge open towards the left so they never overflow the page. --}}
    <div class="relative" x-data="{ open: false, alignRight: false }" @keydown.escape.window="open = false" @click.outside="open = false">
        <button type="button" :aria-expanded="open.toString()" aria-haspopup="true"
                @click="open = ! open; alignRight = $el.getBoundingClientRect().left + 220 > window.innerWidth"
                @class([
                    'inline-flex items-center gap-1 rounded-md px-3 py-2 transition',
                    'bg-emerald-50 font-medium text-emerald-700' => $group['active'],
                    'text-stone-600 hover:bg-stone-100' => ! $group['active'],
                ]) data-nav-group="{{ $group['label'] }}">
            {{ $group['label'] }}
            @if ($badge)
                <span class="rounded-full bg-amber-500 px-1.5 text-xs text-white" data-badge>{{ $badge }}</span>
            @endif
            <svg class="h-4 w-4 transition" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
        </button>

        <div x-show="open" x-transition.opacity x-cloak :class="alignRight ? 'right-0' : 'left-0'"
             class="absolute z-20 mt-1 min-w-48 rounded-xl border border-stone-200 bg-white p-1 shadow-lg">
            @foreach ($group['items'] as $item)
                <a href="{{ $item['url'] }}" @class([
                    'flex items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm',
                    'bg-emerald-50 font-medium text-emerald-700' => $item['active'],
                    'text-stone-700 hover:bg-stone-100' => ! $item['active'],
                ]) @if ($item['active']) aria-current="page" @endif>
                    {{ $item['label'] }}
                    @if ($item['badge'])
                        <span class="rounded-full bg-amber-500 px-1.5 text-xs text-white">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
@endif
