@props(['active' => false])

<a {{ $attributes->class([
    'inline-flex min-h-11 items-center rounded-control px-3 transition',
    'bg-sunken font-bold text-ink' => $active,
    'font-medium text-ink-soft hover:bg-sunken hover:text-ink' => ! $active,
]) }} @if ($active) aria-current="page" @endif>{{ $slot }}</a>
