<div>
    @php($canBuy = $product->availability()->canBuy())
    @php($weighed = $product->unit_type === \App\Enums\UnitType::Weight)

    @if (! $canBuy)
        {{-- Sold out: the controls give way to a quiet placeholder. --}}
        <p @class([
            'flex h-12 items-center justify-center rounded-control border border-dashed border-line-dashed text-sm font-bold text-ink-disabled',
            'mt-4' => $compact,
        ])>Agotado hoy</p>
    @else
        <form wire:submit="add" @class(['space-y-2', 'mt-4' => $compact])>
            @if ($compact && $weighed)
                {{-- WeightSelector: real radios, so arrow keys and screen readers work. --}}
                <fieldset class="min-w-0">
                    <legend class="sr-only">Cantidad de {{ $product->name }}</legend>
                    <div class="grid grid-cols-4 gap-1 rounded-control bg-sunken p-1">
                        @foreach (['0.25' => '¼ kg', '0.5' => '½ kg', '0.75' => '¾ kg', '1' => '1 kg'] as $value => $label)
                            <label class="relative grid h-11 cursor-pointer place-items-center rounded-chip text-sm font-semibold text-ink-soft has-checked:bg-card has-checked:font-extrabold has-checked:text-ink has-checked:shadow-selected has-focus-visible:outline-3 has-focus-visible:outline-offset-2 has-focus-visible:outline-action-ring">
                                <input type="radio" name="peso-{{ $product->id }}" value="{{ $value }}" wire:model.live="quantity" class="sr-only">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @elseif ($compact)
                {{-- UnitStepper --}}
                <div class="flex h-[52px] items-center justify-between rounded-control bg-sunken px-1">
                    <button type="button" wire:click="decrement" aria-label="Quitar una unidad"
                            class="grid h-11 w-11 place-items-center rounded-[10px] text-xl text-ink-soft hover:bg-card disabled:opacity-40" @disabled($quantity <= 1)>−</button>
                    <span class="text-[15px] font-bold" aria-live="polite">{{ (int) $quantity }} {{ (int) $quantity === 1 ? 'unidad' : 'unidades' }}</span>
                    <button type="button" wire:click="increment" aria-label="Añadir una unidad"
                            class="grid h-11 w-11 place-items-center rounded-[10px] text-xl text-ink-soft hover:bg-card">+</button>
                </div>
            @else
                {{-- Product page: any amount, in steps of {{ $product->unit_type->step() }}. --}}
                <div class="flex items-center gap-2">
                    <label class="sr-only" for="qty-{{ $product->id }}">Cantidad</label>
                    <div class="flex h-12 items-center rounded-control border border-line-strong bg-card">
                        <input id="qty-{{ $product->id }}" type="number" wire:model.live.debounce.300ms="quantity"
                               step="{{ $product->unit_type->step() }}" min="{{ $product->unit_type->step() }}"
                               class="h-full w-24 rounded-control border-0 bg-transparent pl-3 text-right text-[15px] font-bold focus:ring-0">
                        <span class="pl-1.5 pr-3 text-[13px] font-semibold text-ink-muted">{{ $product->unit_type->suffix() }}</span>
                    </div>
                </div>
            @endif

            @if ($success)
                <button type="submit" class="flex h-12 w-full items-center justify-center gap-2 rounded-control bg-success text-[15px] font-bold text-on-action">
                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                    Añadido · {{ $product->formatQuantity((float) $quantity) }}
                </button>
            @else
                <button type="submit" wire:loading.attr="disabled" wire:target="add"
                        class="flex h-12 w-full items-center justify-center gap-2 rounded-control bg-action text-[15px] font-bold text-on-action transition hover:bg-action-hover disabled:opacity-70">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    <span wire:loading.remove wire:target="add">Añadir</span>
                    <span wire:loading wire:target="add">Añadiendo…</span>
                </button>
            @endif
        </form>
    @endif

    @if ($message)
        <p role="status" @class(['mt-1.5 text-center text-[13px]', 'text-ink-soft' => $success, 'font-semibold text-red-700' => ! $success])>
            {{ $message }}
            @if ($success && Route::has('cart.index'))
                · <a href="{{ route('cart.index') }}" class="font-bold text-action underline underline-offset-2">Ver carrito</a>
            @endif
        </p>
    @endif
</div>
