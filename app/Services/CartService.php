<?php

namespace App\Services;

use App\Exceptions\CartException;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CartService
{
    /** @return Collection<int, CartItem> */
    public function items(User $user): Collection
    {
        return $user->cartItems()->with(['product.stock', 'product.category'])->oldest('id')->get();
    }

    /**
     * Add a quantity of a product to the cart (merging with an existing line).
     *
     * @throws CartException
     */
    public function add(User $user, Product $product, float $quantity): CartItem
    {
        $this->assertSellable($product);
        $this->assertValidQuantity($product, $quantity);

        $item = $user->cartItems()->firstOrNew(['product_id' => $product->id]);
        $item->quantity = round(($item->quantity ?? 0) + $quantity, 3);
        $item->save();

        return $item;
    }

    public function count(User $user): int
    {
        return $user->cartItems()->count();
    }

    /** @throws CartException */
    protected function assertSellable(Product $product): void
    {
        if (! $product->is_active) {
            throw new CartException("«{$product->name}» ya no está a la venta.");
        }
    }

    /** @throws CartException */
    protected function assertValidQuantity(Product $product, float $quantity): void
    {
        $step = $product->unit_type->step();

        // Quantities must be positive multiples of the product's step (1 ud or 0,25 kg).
        if ($quantity <= 0 || abs(round($quantity / $step) * $step - $quantity) > 0.0001) {
            throw new CartException('Cantidad no válida: usa múltiplos de '.$product->formatQuantity($step).'.');
        }
    }
}
