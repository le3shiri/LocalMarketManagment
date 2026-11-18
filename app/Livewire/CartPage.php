<?php

namespace App\Livewire;

use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Carrito')]
class CartPage extends Component
{
    public function render(CartService $cart): View
    {
        $items = $cart->items(auth()->user());

        return view('livewire.cart-page', [
            'items' => $items,
            'subtotal' => round($items->sum('line_total'), 2),
        ]);
    }
}
