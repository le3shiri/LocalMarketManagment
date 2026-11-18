@props(['product'])

{{-- Sprint 1: a product on sale is available. Sprint 2 refines this with the online stock. --}}
<p {{ $attributes->class('flex items-center gap-1.5 text-xs') }}>
    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
    <span class="text-emerald-700">Disponible</span>
</p>
