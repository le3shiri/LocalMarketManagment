@props(['title', 'subtitle'])

{{-- Sign-in and sign-up: the form on a card, beside the shop's shelves (same section colours as the catalog). --}}
<div class="mx-auto grid max-w-5xl overflow-hidden rounded-shelf bg-card shadow-card md:grid-cols-2">
    <div class="p-8 sm:p-10">
        <h1 class="text-[28px] font-extrabold tracking-tight text-ink">{{ $title }}</h1>
        <p class="mt-1 text-[15px] text-ink-muted">{{ $subtitle }}</p>
        <div class="mt-6">{{ $slot }}</div>
    </div>
    <div class="hidden flex-col justify-between gap-3 bg-page p-8 md:flex" aria-hidden="true">
        <div class="grid gap-3">
            @foreach (['fruta-y-verdura', 'carniceria', 'lacteos-y-huevos', 'panaderia'] as $i => $slug)
                @php($c = \App\Support\SectionColors::for($slug))
                <div class="flex h-16 items-center gap-2 rounded-card px-4 {{ $c['tint'] }}">
                    @for ($n = 0; $n < 3 - ($i % 2); $n++)
                        <span class="h-8 w-16 rounded-control bg-card"></span>
                    @endfor
                </div>
            @endforeach
        </div>
        <p class="text-[15px] font-semibold text-ink-soft">Tu tienda de barrio, estantería a estantería.</p>
    </div>
</div>
