<?php

namespace App\Livewire;

use App\Exceptions\CartException;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Carrito')]
class CartPage extends Component
{
    public ?string $error = null;

    public function increment(CartService $cart, int $itemId): void
    {
        $this->step($cart, $itemId, +1);
    }

    public function decrement(CartService $cart, int $itemId): void
    {
        $this->step($cart, $itemId, -1);
    }

    public function updateQuantity(CartService $cart, int $itemId, $quantity): void
    {
        $this->attempt(fn () => $cart->setQuantity(auth()->user(), $itemId, (float) str_replace(',', '.', (string) $quantity)));
    }

    public function remove(CartService $cart, int $itemId): void
    {
        $cart->remove(auth()->user(), $itemId);
        $this->error = null;
        $this->dispatch('cart-updated');
    }

    public function clear(CartService $cart): void
    {
        $cart->clear(auth()->user());
        $this->error = null;
        $this->dispatch('cart-updated');
    }

    public function render(CartService $cart): View
    {
        $user = auth()->user();
        $items = $cart->items($user);

        return view('livewire.cart-page', [
            'items' => $items,
            'shortages' => $cart->shortages($user),
            'subtotal' => round($items->sum('line_total'), 2),
        ]);
    }

    private function step(CartService $cart, int $itemId, int $direction): void
    {
        /** @var CartItem $item */
        $item = auth()->user()->cartItems()->with('product')->findOrFail($itemId);
        $quantity = $item->quantity + $direction * $item->product->unit_type->step();

        $this->attempt(fn () => $cart->setQuantity(auth()->user(), $itemId, $quantity));
    }

    private function attempt(callable $operation): void
    {
        try {
            $operation();
            $this->error = null;
            $this->dispatch('cart-updated');
        } catch (CartException $e) {
            $this->error = $e->getMessage();
        }
    }
}
