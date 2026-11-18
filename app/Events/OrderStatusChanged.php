<?php

namespace App\Events;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when an order is created or changes status. Dispatched only after the database
 * transaction commits, so nobody is notified about a change that was rolled back.
 */
class OrderStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly Order $order,
        public readonly ?OrderStatus $from,
        public readonly OrderStatus $to,
        public readonly ?string $note = null,
    ) {}
}
