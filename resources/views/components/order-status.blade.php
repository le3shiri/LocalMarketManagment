@props(['status'])

{{-- Order status pill: a dot plus the word, in the status's own tone. --}}
<span {{ $attributes->class(['inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold', $status->badge()]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>{{ $status->label() }}
</span>
