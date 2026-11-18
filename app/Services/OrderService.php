<?php

namespace App\Services;

use App\Enums\Fulfillment;
use App\Enums\OrderStatus;
use App\Exceptions\CartException;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use App\Support\CartTotals;
use App\Support\Slots;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly Slots $slots,
    ) {}

    /**
     * Turn the customer's cart into a pending web order.
     *
     * @param  array{fulfillment: string, contact_name: string, contact_phone: string, delivery_address?: ?string, slot_date: string, slot: string, notes?: ?string}  $data
     *
     * @throws CartException
     */
    public function placeFromCart(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $items = $this->cart->items($user);

            if ($items->isEmpty()) {
                throw new CartException('Tu carrito está vacío.');
            }

            $fulfillment = Fulfillment::from($data['fulfillment']);
            $day = CarbonImmutable::parse($data['slot_date']);

            if (! $this->slots->hasRoom($day, $data['slot'])) {
                throw new CartException('La franja elegida ya está completa. Elige otra, por favor.');
            }

            $totals = CartTotals::for($items, $fulfillment);

            $order = Order::create([
                'user_id' => $user->id,
                'channel' => 'web',
                'status' => OrderStatus::Pending,
                'fulfillment' => $fulfillment,
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'delivery_address' => $fulfillment === Fulfillment::Delivery ? $data['delivery_address'] : null,
                'slot_date' => $day,
                'slot' => $data['slot'],
                'subtotal' => $totals->subtotal,
                'delivery_fee' => $totals->deliveryFee,
                'total' => $totals->total,
                'notes' => $data['notes'] ?? null,
            ]);

            $order->items()->createMany($items->map(fn (CartItem $item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'sku' => $item->product->sku,
                'unit_type' => $item->product->unit_type,
                'unit_price' => $item->product->price,
                'quantity' => $item->quantity,
                'line_total' => $item->line_total,
            ])->all());

            $this->cart->clear($user);

            return $order->refresh();
        });
    }
}
