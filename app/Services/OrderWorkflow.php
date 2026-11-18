<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\OrderTransitionException;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Moves orders through their life cycle. Every change is validated against the allowed
 * transitions, written to the order timeline and to the audit log, and keeps the stock
 * in sync: delivery consumes the reservation, cancellation releases it.
 */
class OrderWorkflow
{
    public function __construct(private readonly StockService $stock) {}

    /**
     * Move the order one step forward (pending → preparing → ready → delivered).
     *
     * @param  array<int, float>  $weights  order_item_id => real quantity, for weighted lines on delivery
     *
     * @throws OrderTransitionException
     */
    public function advance(Order $order, OrderStatus $target, ?User $user, ?string $note = null, array $weights = []): Order
    {
        return DB::transaction(function () use ($order, $target, $user, $note, $weights) {
            $order = $this->lock($order);

            if (! $order->status->canTransitionTo($target)) {
                throw new OrderTransitionException(
                    "El pedido {$order->code} está «{$order->status->label()}» y no puede pasar a «{$target->label()}»."
                );
            }

            if ($target === OrderStatus::Delivered) {
                $this->deliver($order, $user, $weights);
            }

            return $this->transition($order, $target, $user, $note);
        });
    }

    /** Record the initial status of a freshly created order. */
    public function recordCreation(Order $order, ?User $user): void
    {
        $order->statusChanges()->create([
            'user_id' => $user?->id,
            'from_status' => null,
            'to_status' => $order->status,
        ]);
    }

    /**
     * Consume the reservation and settle real weights; the order total is recalculated
     * because weighted products are charged by what was actually weighed.
     */
    protected function deliver(Order $order, ?User $user, array $weights): void
    {
        $order->load('items');
        $lines = [];

        foreach ($order->items as $item) {
            /** @var OrderItem $item */
            $delivered = isset($weights[$item->id]) ? round((float) $weights[$item->id], 3) : $item->quantity;

            $item->delivered_quantity = $delivered;
            $item->line_total = round((float) $item->unit_price * $delivered, 2);
            $item->save();

            if ($item->product_id) {
                $lines[$item->product_id] = ['reserved' => $item->quantity, 'delivered' => $delivered];
            }
        }

        try {
            $this->stock->deliver($lines, $user, $order->code);
        } catch (InvalidArgumentException $e) {
            throw new OrderTransitionException($e->getMessage(), previous: $e);
        }

        $order->subtotal = round($order->items->sum(fn (OrderItem $i) => (float) $i->line_total), 2);
        $order->total = round((float) $order->subtotal + (float) $order->delivery_fee, 2);
    }

    protected function transition(Order $order, OrderStatus $target, ?User $user, ?string $note): Order
    {
        $from = $order->status;

        $order->status = $target;
        $order->save();

        $order->statusChanges()->create([
            'user_id' => $user?->id,
            'from_status' => $from,
            'to_status' => $target,
            'note' => $note,
        ]);

        AuditLog::record('order', "Pedido {$order->code}: {$from->label()} → {$target->label()}", $order, $note ? ['motivo' => $note] : null, $user);

        return $order;
    }

    /** Re-read the order with a row lock so two employees can't change it at the same time. */
    protected function lock(Order $order): Order
    {
        return Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
    }
}
