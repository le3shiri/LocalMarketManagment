@props(['active' => false])

<a {{ $attributes->class([
    'rounded-md px-3 py-2 transition',
    'bg-emerald-50 font-medium text-emerald-700' => $active,
    'text-stone-600 hover:bg-stone-100' => ! $active,
]) }}>{{ $slot }}</a>
