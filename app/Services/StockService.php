<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockBatch;
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
    public function registerEntry(Product $product, float $storeQty, float $onlineQty, User $user, ?string $reference = null, ?string $reason = null, ?string $lotCode = null, ?string $expiresAt = null): ProductStock
    {
        if ($storeQty < 0 || $onlineQty < 0 || $storeQty + $onlineQty <= 0) {
            throw new InvalidArgumentException('La entrada debe ser mayor que cero y sin cantidades negativas.');
        }

        return DB::transaction(function () use ($product, $storeQty, $onlineQty, $user, $reference, $reason, $lotCode, $expiresAt) {
            $stock = $this->lock($product);

            $stock->store_qty += $storeQty;
            $stock->online_qty += $onlineQty;
            $stock->on_hand += $storeQty + $onlineQty;
            $stock->save();

            $this->log($stock, MovementType::Entry, $user, store: $storeQty, online: $onlineQty, reference: $reference, reason: $reason);

            // Every receipt is a batch; perishable ones carry their lot and expiry date.
            StockBatch::create([
                'product_id' => $product->id,
                'lot_code' => $lotCode ?: ($reference ?: 'ENT-'.now()->format('Ymd-His')),
                'expires_at' => $expiresAt,
                'quantity_received' => $storeQty + $onlineQty,
                'quantity_remaining' => $storeQty + $onlineQty,
                'received_by' => $user->id,
                'reference' => $reference,
            ]);

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
     * Goods leaving the stock for a reason other than a sale (supplier return, internal
     * use, donation, damage…). From the store, or from the free online stock: units
     * reserved for orders are never touched and nothing goes below zero.
     *
     * @throws InvalidArgumentException with a user-facing message
     */
    public function removeStock(Product $product, float $quantity, string $channel, MovementType $type, User $user, string $reason, ?string $reference = null): ProductStock
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor que cero.');
        }

        return DB::transaction(function () use ($product, $quantity, $channel, $type, $user, $reason, $reference) {
            $stock = $this->lock($product);

            $available = $channel === 'online' ? $stock->online_qty - $stock->reserved_qty : $stock->store_qty;
            if ($quantity > $available + 0.0005) {
                $where = $channel === 'online' ? 'online sin reservar' : 'en tienda';
                throw new InvalidArgumentException("Solo hay {$product->formatQuantity(max(0, $available))} de «{$product->name}» {$where}.");
            }

            if ($channel === 'online') {
                $stock->online_qty = round($stock->online_qty - $quantity, 3);
            } else {
                $stock->store_qty = round($stock->store_qty - $quantity, 3);
            }
            $stock->on_hand = max(0, round($stock->on_hand - $quantity, 3));
            $stock->save();

            $this->log($stock, $type, $user,
                store: $channel === 'online' ? 0 : -$quantity,
                online: $channel === 'online' ? -$quantity : 0,
                reference: $reference, reason: $reason);

            return $stock;
        });
    }

    /**
     * Correct the real units after a physical count. The difference is applied to the
     * store stock; if the count is so low that the store would go negative, the rest comes
     * out of the free online stock. Units reserved for orders can never be adjusted away.
     *
     * @return float the difference applied (counted − recorded)
     *
     * @throws InvalidArgumentException with a user-facing message
     */
    public function adjustCount(Product $product, float $counted, User $user, string $reason): float
    {
        if ($counted < 0) {
            throw new InvalidArgumentException('La cantidad contada no puede ser negativa.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('El motivo del ajuste es obligatorio.');
        }

        return DB::transaction(function () use ($product, $counted, $user, $reason) {
            $stock = $this->lock($product);
            $difference = round($counted - $stock->on_hand, 3);

            if (abs($difference) < 0.0005) {
                return 0.0;
            }

            $storeDelta = $difference;
            $onlineDelta = 0.0;

            if ($stock->store_qty + $difference < 0) {
                $storeDelta = -$stock->store_qty;
                $onlineDelta = round($difference - $storeDelta, 3); // negative
                $freeOnline = $stock->online_qty - $stock->reserved_qty;

                if (-$onlineDelta > $freeOnline + 0.0005) {
                    throw new InvalidArgumentException(
                        "El recuento ({$product->formatQuantity($counted)}) es inferior a lo reservado para pedidos ({$product->formatQuantity($stock->reserved_qty)}). Revisa los pedidos pendientes antes de ajustar."
                    );
                }
            }

            $stock->store_qty = round($stock->store_qty + $storeDelta, 3);
            $stock->online_qty = round($stock->online_qty + $onlineDelta, 3);
            $stock->on_hand = round($counted, 3);
            $stock->save();

            $this->log($stock, MovementType::Adjustment, $user, store: $storeDelta, online: $onlineDelta,
                reason: $reason.' (contado '.$product->formatQuantity($counted).', diferencia '.($difference > 0 ? '+' : '').$product->formatQuantity($difference).')');

            return $difference;
        });
    }

    /**
     * Move units between channels without changing the real units (e.g. mid-morning, when
     * online sells out early and the shop has plenty). Online → store can only move units
     * that aren't reserved for orders.
     *
     * @param  string  $to  'online' or 'store'
     *
     * @throws InvalidArgumentException with a user-facing message
     */
    public function transfer(Product $product, float $quantity, string $to, User $user, string $reason): ProductStock
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('La cantidad a transferir debe ser mayor que cero.');
        }

        return DB::transaction(function () use ($product, $quantity, $to, $user, $reason) {
            $stock = $this->lock($product);
            $available = $to === 'online' ? $stock->store_qty : $stock->online_qty - $stock->reserved_qty;

            if ($quantity > $available + 0.0005) {
                $from = $to === 'online' ? 'en tienda' : 'online sin reservar';
                throw new InvalidArgumentException("No se puede transferir {$product->formatQuantity($quantity)}: solo hay {$product->formatQuantity(max(0, $available))} {$from}.");
            }

            $sign = $to === 'online' ? 1 : -1;
            $stock->online_qty = round($stock->online_qty + $sign * $quantity, 3);
            $stock->store_qty = round($stock->store_qty - $sign * $quantity, 3);
            $stock->save();

            $this->log($stock, MovementType::Transfer, $user, store: -$sign * $quantity, online: $sign * $quantity, reason: $reason);

            return $stock;
        });
    }

    /**
     * Take the remaining units of an expired batch out of the sellable stock so they can't
     * be sold online or at the till: first from the store, then from free online stock.
     * Units already reserved for orders are left reserved (staff must offer a substitute),
     * which the ledger notes. The batch stays "expired" until someone confirms it was
     * physically withdrawn.
     *
     * @return float units blocked
     */
    public function expireBatch(StockBatch $batch, ?User $user = null): float
    {
        return DB::transaction(function () use ($batch, $user) {
            $batch = StockBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();

            if ($batch->status !== StockBatch::ACTIVE) {
                return 0.0;
            }

            $stock = ProductStock::where('product_id', $batch->product_id)->lockForUpdate()->firstOrFail();
            $quantity = $batch->quantity_remaining;

            $fromStore = min($stock->store_qty, $quantity);
            $fromOnline = min(max(0, $stock->online_qty - $stock->reserved_qty), round($quantity - $fromStore, 3));
            $blocked = round($fromStore + $fromOnline, 3);

            $stock->store_qty = round($stock->store_qty - $fromStore, 3);
            $stock->online_qty = round($stock->online_qty - $fromOnline, 3);
            $stock->on_hand = max(0, round($stock->on_hand - $blocked, 3));
            $stock->save();

            $batch->status = StockBatch::EXPIRED;
            $batch->save();

            $reason = "Lote {$batch->lot_code} caducado el {$batch->expires_at?->format('d/m/Y')}";
            if ($blocked + 0.0005 < $quantity) {
                $reason .= ' · '.round($quantity - $blocked, 3).' reservado(s) en pedidos: ofrecer sustituto';
            }

            $this->log($stock, MovementType::Expiry, $user, store: -$fromStore, online: -$fromOnline, reference: $batch->lot_code, reason: $reason, consumeBatches: false);

            return $blocked;
        });
    }

    /**
     * Returned goods that can be sold again go back to the store shelf.
     *
     * @param  array<int, float>  $lines  product_id => quantity
     */
    public function restockReturn(array $lines, ?User $user, string $reference, ?string $reason = null): void
    {
        DB::transaction(function () use ($lines, $user, $reference, $reason) {
            foreach ($this->lockMany(array_keys($lines)) as $productId => $stock) {
                $stock->store_qty = round($stock->store_qty + $lines[$productId], 3);
                $stock->on_hand = round($stock->on_hand + $lines[$productId], 3);
                $stock->save();

                $this->log($stock, MovementType::Return, $user, store: $lines[$productId], reference: $reference, reason: $reason);
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

    /**
     * Units that physically leave the shop (sale, delivery, exit, negative count) are taken
     * from batches, first-expiring first (FEFO). Stock without batches is simply ignored.
     */
    protected function consumeBatches(int $productId, float $quantity): void
    {
        foreach (StockBatch::where('product_id', $productId)->available()->lockForUpdate()->get() as $batch) {
            if ($quantity <= 0.0005) {
                break;
            }

            $taken = min($batch->quantity_remaining, $quantity);
            $batch->quantity_remaining = round($batch->quantity_remaining - $taken, 3);
            $batch->save();
            $quantity = round($quantity - $taken, 3);
        }
    }

    protected function log(ProductStock $stock, MovementType $type, ?User $user, float $store = 0, float $online = 0, float $reserved = 0, ?string $reference = null, ?string $reason = null, bool $consumeBatches = true): void
    {
        $leaving = -($store + $online);
        if ($consumeBatches && $type !== MovementType::Transfer && $leaving > 0) {
            $this->consumeBatches($stock->product_id, $leaving);
        }

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
