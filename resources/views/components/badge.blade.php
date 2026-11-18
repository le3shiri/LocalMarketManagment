@props(['tone' => 'neutral'])

{{-- Small pill for states: success, warning, danger, action or neutral. --}}
<span {{ $attributes->class([
    'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-bold',
    'bg-success-soft text-success-ink' => $tone === 'success',
    'bg-warn-soft text-stock-low' => $tone === 'warning',
    'bg-red-50 text-red-800' => $tone === 'danger',
    'bg-action-soft text-action' => $tone === 'action',
    'bg-sunken text-ink-soft' => $tone === 'neutral',
]) }}>{{ $slot }}</span>
