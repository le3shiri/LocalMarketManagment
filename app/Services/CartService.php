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
        $newQuantity = round(($item->quantity ?? 0) + $quantity, 3);

        $this->assertAvailable($product, $newQuantity, alreadyInCart: $item->quantity ?? 0);

        $item->quantity = $newQuantity;
        $item->save();

        return $item;
    }

    /**
     * Set a line to an exact quantity; zero or less removes it.
     *
     * @throws CartException
     */
    public function setQuantity(User $user, int $itemId, float $quantity): ?CartItem
    {
        $item = $user->cartItems()->with('product')->findOrFail($itemId);

        if ($quantity <= 0) {
            $item->delete();

            return null;
        }

        $this->assertValidQuantity($item->product, $quantity);

        // Lowering a quantity must always be possible, even if stock dropped meanwhile.
        if ($quantity > $item->quantity) {
            $this->assertAvailable($item->product, $quantity);
        }

        $item->update(['quantity' => round($quantity, 3)]);

        return $item;
    }

    public function remove(User $user, int $itemId): void
    {
        $user->cartItems()->whereKey($itemId)->delete();
    }

    public function clear(User $user): void
    {
        $user->cartItems()->delete();
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

    /**
     * Lines whose quantity is no longer covered by the online stock (it changed after
     * the customer added them). Keyed by cart item id => units still available.
     *
     * @return array<int, float>
     */
    public function shortages(User $user): array
    {
        return $this->items($user)
            ->filter(fn (CartItem $item) => $item->quantity > $item->product->stock->available_online)
            ->mapWithKeys(fn (CartItem $item) => [$item->id => $item->product->stock->available_online])
            ->all();
    }

    /**
     * The cart may never hold more than the online channel can sell (stock online - reserved).
     *
     * @throws CartException
     */
    protected function assertAvailable(Product $product, float $quantity, float $alreadyInCart = 0): void
    {
        $available = $product->stock()->first()?->available_online ?? 0;

        if ($quantity <= $available) {
            return;
        }

        if ($available <= 0) {
            throw new CartException("«{$product->name}» está agotado online.");
        }

        $message = "Solo quedan {$product->formatQuantity($available)} de «{$product->name}».";

        if ($alreadyInCart > 0) {
            $message .= " Ya tienes {$product->formatQuantity($alreadyInCart)} en el carrito.";
        }

        throw new CartException($message);
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
