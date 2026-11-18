@props(['variant' => 'primary'])

<button {{ $attributes->merge(['type' => 'submit'])->class([
    'inline-flex min-h-11 items-center justify-center gap-2 rounded-control px-5 text-[15px] font-bold transition disabled:cursor-not-allowed disabled:opacity-60',
    'bg-action text-on-action hover:bg-action-hover' => $variant === 'primary',
    'border border-line-strong bg-card text-ink hover:bg-sunken' => $variant === 'secondary',
    'bg-red-700 text-white hover:bg-red-800' => $variant === 'danger',
]) }}>{{ $slot }}</button>
