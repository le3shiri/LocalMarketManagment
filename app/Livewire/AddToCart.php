<?php

namespace App\Livewire;

use App\Exceptions\CartException;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AddToCart extends Component
{
    #[Locked]
    public int $productId;

    public float $quantity;

    public bool $compact = false;

    public ?string $message = null;

    public bool $success = false;

    public function mount(Product $product, bool $compact = false): void
    {
        $this->productId = $product->id;
        $this->quantity = $product->unit_type->step();
        $this->compact = $compact;
    }

    /** Changing the amount clears the previous "Añadido" confirmation. */
    public function updatedQuantity(): void
    {
        $this->reset('success', 'message');
    }

    /** Unit stepper (+). Stock limits are checked by CartService when adding. */
    public function increment(): void
    {
        $this->quantity += $this->product()->unit_type->step();
        $this->updatedQuantity();
    }

    /** Unit stepper (−), never below one step. */
    public function decrement(): void
    {
        $step = $this->product()->unit_type->step();
        $this->quantity = max($step, $this->quantity - $step);
        $this->updatedQuantity();
    }

    public function add(CartService $cart)
    {
        if (! auth()->check()) {
            session()->put('url.intended', route('catalog.show', $this->product()));

            return $this->redirectRoute('login');
        }

        abort_unless(auth()->user()->can('cart.use'), 403);

        try {
            $cart->add(auth()->user(), $this->product(), (float) $this->quantity);
            $this->success = true;
            $this->message = 'Añadido al carrito';
            $this->dispatch('cart-updated');
        } catch (CartException $e) {
            $this->success = false;
            $this->message = $e->getMessage();
        }
    }

    public function product(): Product
    {
        return Product::with('stock')->findOrFail($this->productId);
    }

    public function render(): View
    {
        return view('livewire.add-to-cart', ['product' => $this->product()]);
    }
}
