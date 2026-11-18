@props(['title', 'subtitle' => null, 'back' => null, 'backLabel' => null])

{{-- Page header shared by every screen: optional back link, title, subtitle and actions on the right. --}}
<div {{ $attributes->class('mb-6') }}>
    @if ($back)
        <a href="{{ $back }}" class="mb-1 inline-flex min-h-11 items-center gap-1.5 text-[15px] font-bold text-action hover:underline">
            <span aria-hidden="true">←</span> {{ $backLabel ?? 'Volver' }}
        </a>
    @endif
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-[28px] font-extrabold leading-tight tracking-tight text-ink">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1 text-[15px] text-ink-muted">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
</div>
