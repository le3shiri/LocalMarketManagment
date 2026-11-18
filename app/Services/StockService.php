<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The only place that writes to product_stocks. Every change runs inside a transaction
 * with the stock row locked, and leaves a line in the movement ledger.
 */
class StockService
{
    /**
     * Register received goods and split them between the store and the online channel.
     */
    public function registerEntry(Product $product, float $storeQty, float $onlineQty, User $user, ?string $reference = null, ?string $reason = null): ProductStock
    {
        if ($storeQty < 0 || $onlineQty < 0 || $storeQty + $onlineQty <= 0) {
            throw new InvalidArgumentException('La entrada debe ser mayor que cero y sin cantidades negativas.');
        }

        return DB::transaction(function () use ($product, $storeQty, $onlineQty, $user, $reference, $reason) {
            $stock = $this->lock($product);

            $stock->store_qty += $storeQty;
            $stock->online_qty += $onlineQty;
            $stock->on_hand += $storeQty + $onlineQty;
            $stock->save();

            $this->log($stock, MovementType::Entry, $user, store: $storeQty, online: $onlineQty, reference: $reference, reason: $reason);

            return $stock;
        });
    }

    /** Lock the product's stock row for the rest of the current transaction. */
    protected function lock(Product $product): ProductStock
    {
        return ProductStock::where('product_id', $product->id)->lockForUpdate()->firstOrFail();
    }

    protected function log(ProductStock $stock, MovementType $type, ?User $user, float $store = 0, float $online = 0, float $reserved = 0, ?string $reference = null, ?string $reason = null): void
    {
        StockMovement::create([
            'product_id' => $stock->product_id,
            'user_id' => $user?->id,
            'type' => $type,
            'store_delta' => $store,
            'online_delta' => $online,
            'reserved_delta' => $reserved,
            'on_hand_after' => $stock->on_hand,
            'reference' => $reference,
            'reason' => $reason,
        ]);
    }
}
