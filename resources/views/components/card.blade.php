@props(['title' => null])

<section {{ $attributes->class('rounded-xl border border-stone-200 bg-white p-6 shadow-sm') }}>
    @if ($title)
        <h2 class="mb-4 text-lg font-semibold text-stone-900">{{ $title }}</h2>
    @endif
    {{ $slot }}
</section>
