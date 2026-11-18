<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Live status of a customer's order. Polls every 30 s so the customer sees the change
 * to "Listo" without reloading (fewer calls to the shop asking if it's ready).
 */
class OrderTracker extends Component
{
    #[Locked]
    public int $orderId;

    public function mount(Order $order): void
    {
        abort_unless($order->user_id === auth()->id(), 404);
        $this->orderId = $order->id;
    }

    public function render(): View
    {
        $order = Order::with('statusChanges')->findOrFail($this->orderId);
        $reached = $order->statusChanges->keyBy(fn ($change) => $change->to_status->value);

        $steps = collect([OrderStatus::Pending, OrderStatus::Preparing, OrderStatus::Ready, OrderStatus::Delivered])
            ->map(fn (OrderStatus $status) => [
                'status' => $status,
                'at' => $reached[$status->value]->created_at ?? null,
                'done' => isset($reached[$status->value]),
                'current' => $order->status === $status,
            ]);

        return view('livewire.order-tracker', [
            'order' => $order,
            'steps' => $steps,
            'cancellation' => $reached[OrderStatus::Cancelled->value] ?? null,
        ]);
    }
}
