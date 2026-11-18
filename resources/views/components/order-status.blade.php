@props(['status'])

<span {{ $attributes->class(['whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium', $status->badge()]) }}>{{ $status->label() }}</span>
