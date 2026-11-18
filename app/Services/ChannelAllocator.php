<?php

namespace App\Services;

use App\Enums\UnitType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use InvalidArgumentException;

/**
 * Applies the owner's channel split ("reparto inicial", section 5): a share of each product's
 * stock goes online, a more prudent share for fresh (perishable) products. Implemented as
 * ordinary transfers, so every change is in the ledger.
 */
class ChannelAllocator
{
    public function __construct(private readonly StockService $stock) {}

    public function shareFor(Product $product): int
    {
        return (int) Setting::get($product->is_perishable ? 'online_share_fresh' : 'online_share');
    }

    /** Units that should be online for a product under the current shares. */
    public function target(Product $product): float
    {
        $stock = $product->stock;
        $total = $stock->store_qty + $stock->online_qty;
        $target = $total * $this->shareFor($product) / 100;

        // Whole units for products sold by the unit; never below what is already promised.
        $target = $product->unit_type === UnitType::Unit ? floor($target) : round($target, 2);

        return max($target, $stock->reserved_qty);
    }

    /**
     * Rebalance every active product (optionally one category) to its target.
     *
     * @return int number of products changed
     */
    public function rebalance(User $user, ?Category $category = null): int
    {
        $changed = 0;

        Product::active()->with('stock')
            ->when($category, fn ($q) => $q->whereBelongsTo($category))
            ->each(function (Product $product) use ($user, &$changed) {
                $difference = round($this->target($product) - $product->stock->online_qty, 3);

                if (abs($difference) < 0.0005) {
                    return;
                }

                try {
                    $this->stock->transfer($product, abs($difference), $difference > 0 ? 'online' : 'store', $user,
                        "Reparto automático ({$this->shareFor($product)} % online)");
                    $changed++;
                } catch (InvalidArgumentException) {
                    // Nothing movable for this product right now; leave it as it is.
                }
            });

        return $changed;
    }
}
