<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\AuditLog;
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

            AuditLog::record('stock', "Entrada de {$product->formatQuantity($storeQty + $onlineQty)} de «{$product->name}»", $product, [
                'tienda' => $storeQty, 'online' => $onlineQty, 'albarán' => $reference,
            ], $user);

            return $stock;
        });
    }

    /**
     * Reserve online stock for every line of an order in one indivisible operation:
     * either all lines are reserved or none is (proposal, section 5 "Reserva indivisible").
     * Rows are locked in product id order so concurrent checkouts can't deadlock.
     *
     * @param  array<int, float>  $lines  product_id => quantity
     *
     * @throws InsufficientStockException
     */
    public function reserve(array $lines, ?User $user, string $reference): void
    {
        DB::transaction(function () use ($lines, $user, $reference) {
            ksort($lines);

            $stocks = ProductStock::with('product')
                ->whereIn('product_id', array_keys($lines))
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            $shortages = [];
            foreach ($lines as $productId => $quantity) {
                $stock = $stocks[$productId] ?? null;
                if (! $stock || ! $stock->product->is_active || $stock->available_online < $quantity) {
                    $shortages[$productId] = $stock?->available_online ?? 0.0;
                }
            }

            if ($shortages !== []) {
                throw new InsufficientStockException($shortages, $stocks);
            }

            foreach ($lines as $productId => $quantity) {
                $stock = $stocks[$productId];
                $stock->reserved_qty = round($stock->reserved_qty + $quantity, 3);
                $stock->save();

                $this->log($stock, MovementType::Reserve, $user, reserved: $quantity, reference: $reference);
            }
        });
    }

    /**
     * Hand an order to the customer: the reserved units leave the online stock and the
     * shop. For weighted products the real weight may differ from what was reserved; the
     * difference is taken from (or returned to) the free online stock and noted in the ledger.
     *
     * @param  array<int, array{reserved: float, delivered: float}>  $lines  product_id => quantities
     *
     * @throws InvalidArgumentException when the extra weight isn't available online
     */
    public function deliver(array $lines, ?User $user, string $reference): void
    {
        DB::transaction(function () use ($lines, $user, $reference) {
            foreach ($this->lockMany(array_keys($lines)) as $productId => $stock) {
                ['reserved' => $reserved, 'delivered' => $delivered] = $lines[$productId];

                // Units online that nobody else has promised: ours (reserved) plus the free ones.
                $usable = $stock->online_qty - max(0, $stock->reserved_qty - $reserved);
                if ($delivered < 0 || $delivered > $usable + 0.0005) {
                    throw new InvalidArgumentException(
                        "No hay stock online suficiente para entregar {$stock->product->formatQuantity($delivered)} de «{$stock->product->name}»."
                    );
                }

                $stock->reserved_qty = max(0, round($stock->reserved_qty - $reserved, 3));
                $stock->online_qty = round($stock->online_qty - $delivered, 3);
                $stock->on_hand = max(0, round($stock->on_hand - $delivered, 3));
                $stock->save();

                $reason = abs($delivered - $reserved) > 0.0005
                    ? 'Peso real '.$stock->product->formatQuantity($delivered).' (pedido '.$stock->product->formatQuantity($reserved).')'
                    : null;

                $this->log($stock, MovementType::Delivery, $user, online: -$delivered, reserved: -$reserved, reference: $reference, reason: $reason);
            }
        });
    }

    /**
     * Counter sale: units leave the store stock and the shop. The online channel is never
     * touched, so a sale at the till can't take units promised to web/WhatsApp orders.
     *
     * @param  array<int, float>  $lines  product_id => quantity
     *
     * @throws InvalidArgumentException when the store stock doesn't cover a line
     */
    public function sellAtCounter(array $lines, ?User $user, string $reference): void
    {
        DB::transaction(function () use ($lines, $user, $reference) {
            $stocks = $this->lockMany(array_keys($lines));

            foreach ($lines as $productId => $quantity) {
                $stock = $stocks->get($productId);

                if (! $stock || $stock->store_qty + 0.0005 < $quantity) {
                    $product = $stock?->product;
                    throw new InvalidArgumentException($product
                        ? "Según el sistema solo hay {$product->formatQuantity($stock->store_qty)} de «{$product->name}» en tienda. Revisa el recuento o haz una transferencia desde almacén."
                        : 'Producto sin stock registrado.');
                }

                $stock->store_qty = round($stock->store_qty - $quantity, 3);
                $stock->on_hand = max(0, round($stock->on_hand - $quantity, 3));
                $stock->save();

                $this->log($stock, MovementType::Sale, $user, store: -$quantity, reference: $reference);
            }
        });
    }

    /**
     * Give reserved units back to the online stock (cancelled order or expired cart).
     *
     * @param  array<int, float>  $lines  product_id => reserved quantity
     */
    public function release(array $lines, ?User $user, string $reference, ?string $reason = null): void
    {
        DB::transaction(function () use ($lines, $user, $reference, $reason) {
            foreach ($this->lockMany(array_keys($lines)) as $productId => $stock) {
                $quantity = min($lines[$productId], $stock->reserved_qty);
                $stock->reserved_qty = round($stock->reserved_qty - $quantity, 3);
                $stock->save();

                $this->log($stock, MovementType::Release, $user, reserved: -$quantity, reference: $reference, reason: $reason);
            }
        });
    }

    /**
     * Lock several stock rows in product id order (consistent order = no deadlocks).
     *
     * @param  array<int>  $productIds
     * @return \Illuminate\Support\Collection<int, ProductStock>
     */
    protected function lockMany(array $productIds)
    {
        return ProductStock::with('product')
            ->whereIn('product_id', $productIds)
            ->orderBy('product_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');
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
