@props(['title' => null])

<section {{ $attributes->class('rounded-card border border-line bg-card p-6 shadow-card') }}>
    @if ($title)
        <h2 class="mb-4 text-lg font-extrabold tracking-tight text-ink">{{ $title }}</h2>
    @endif
    {{ $slot }}
</section>
