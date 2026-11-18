<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Notifications\OrderStatusNotification;

class NotifyCustomerOfOrderStatus
{
    public function handle(OrderStatusChanged $event): void
    {
        // Guests from phone/WhatsApp have no account to notify (the WhatsApp assistant covers them in phase 2).
        $customer = $event->order->user;

        if (! $customer || ! in_array($event->to, OrderStatusNotification::NOTIFIED, true)) {
            return;
        }

        $customer->notify(new OrderStatusNotification($event->order, $event->to, $event->note));
    }
}
