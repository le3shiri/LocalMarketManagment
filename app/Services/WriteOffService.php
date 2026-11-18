<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Enums\WriteOffType;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\User;
use App\Models\WriteOff;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WriteOffService
{
    public function __construct(private readonly StockService $stock) {}

    /**
     * Record a loss. When a batch is given the units come from that batch (e.g. a broken
     * box of a specific lot); otherwise FEFO applies like any other exit.
     *
     * @throws InvalidArgumentException with a user-facing message
     */
    public function record(Product $product, float $quantity, string $channel, WriteOffType $type, User $user, ?StockBatch $batch = null, ?string $notes = null): WriteOff
    {
        if ($batch && ($batch->product_id !== $product->id || $batch->status !== StockBatch::ACTIVE)) {
            throw new InvalidArgumentException('El lote no corresponde a este producto o ya no está activo.');
        }

        if ($batch && $quantity > $batch->quantity_remaining + 0.0005) {
            throw new InvalidArgumentException("En el lote {$batch->lot_code} solo quedan {$product->formatQuantity($batch->quantity_remaining)}.");
        }

        return DB::transaction(function () use ($product, $quantity, $channel, $type, $user, $batch, $notes) {
            $reason = 'Merma: '.$type->label().($notes ? " · {$notes}" : '');

            $this->stock->removeStock($product, $quantity, $channel, MovementType::WriteOff, $user, $reason,
                reference: $batch?->lot_code, consumeBatches: ! $batch);

            if ($batch) {
                $batch->decrement('quantity_remaining', $quantity);
            }

            $writeOff = WriteOff::create([
                'product_id' => $product->id,
                'stock_batch_id' => $batch?->id,
                'user_id' => $user->id,
                'type' => $type,
                'channel' => $channel,
                'quantity' => $quantity,
                'value' => round((float) $product->price * $quantity, 2),
                'notes' => $notes,
            ]);

            AuditLog::record('stock', "Merma de {$product->formatQuantity($quantity)} de «{$product->name}» ({$type->label()})", $product, user: $user, sensitive: true);

            return $writeOff;
        });
    }
}
