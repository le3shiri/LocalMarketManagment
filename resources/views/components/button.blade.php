@props(['variant' => 'primary'])

<button {{ $attributes->merge(['type' => 'submit'])->class([
    'inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:opacity-50',
    'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500' => $variant === 'primary',
    'border border-stone-300 bg-white text-stone-700 hover:bg-stone-50 focus:ring-stone-400' => $variant === 'secondary',
    'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500' => $variant === 'danger',
]) }}>{{ $slot }}</button>
