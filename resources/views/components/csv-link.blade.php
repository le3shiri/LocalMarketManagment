@props(['href'])

{{-- Export button shared by every report; the link carries the current filters. --}}
<a href="{{ $href }}" {{ $attributes->class('no-print inline-flex items-center gap-1 rounded-control border border-line-strong bg-white px-3 py-2 text-sm font-medium text-ink-soft hover:bg-page') }} data-export>
    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3v12m0 0-4-4m4 4 4-4M5 21h14"/></svg>
    Exportar CSV
</a>
