<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\OrderTransitionException;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Moves orders through their life cycle. Every change is validated against the allowed
 * transitions, written to the order timeline and to the audit log.
 */
class OrderWorkflow
{
    /**
     * Move the order one step forward (pending → preparing → ready → delivered).
     *
     * @throws OrderTransitionException
     */
    public function advance(Order $order, OrderStatus $target, ?User $user, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $target, $user, $note) {
            $order = $this->lock($order);

            if (! $order->status->canTransitionTo($target)) {
                throw new OrderTransitionException(
                    "El pedido {$order->code} está «{$order->status->label()}» y no puede pasar a «{$target->label()}»."
                );
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
