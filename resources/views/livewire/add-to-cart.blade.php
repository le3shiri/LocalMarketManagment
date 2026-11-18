<div>
    @php($canBuy = $product->availability()->canBuy())
    <form wire:submit="add" @class(['flex items-center gap-2', 'mt-3' => $compact])>
        <label class="sr-only" for="qty-{{ $product->id }}">Cantidad</label>
        <div class="flex items-center rounded-lg border border-stone-300 bg-white">
            <input id="qty-{{ $product->id }}" type="number" wire:model="quantity"
                   step="{{ $product->unit_type->step() }}" min="{{ $product->unit_type->step() }}"
                   @disabled(! $canBuy)
                   @class(['rounded-lg border-0 py-1.5 text-right text-sm focus:ring-0', 'w-14' => $compact, 'w-20' => ! $compact])>
            <span class="pr-2 text-xs text-stone-500">{{ $product->unit_type->suffix() }}</span>
        </div>
        <button type="submit" @disabled(! $canBuy) wire:loading.attr="disabled"
                @class([
                    'flex-1 rounded-lg px-3 py-1.5 text-sm font-medium transition',
                    'bg-emerald-600 text-white hover:bg-emerald-700' => $canBuy,
                    'cursor-not-allowed bg-stone-200 text-stone-500' => ! $canBuy,
                ])>
            {{ $canBuy ? 'Añadir' : 'Agotado' }}
        </button>
    </form>
    @if ($message)
        <p @class(['mt-1 text-xs', 'text-emerald-700' => $success, 'text-red-600' => ! $success])>
            {{ $message }}
            @if ($success && Route::has('cart.index'))
                · <a href="{{ route('cart.index') }}" class="font-medium underline">Ver carrito</a>
            @endif
        </p>
    @endif
</div>
