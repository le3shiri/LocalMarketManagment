<?php

namespace App\Services;

use App\Enums\Fulfillment;
use App\Enums\OrderStatus;
use App\Exceptions\CartException;
use App\Models\AuditLog;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\CartTotals;
use App\Support\Slots;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Creates orders for every channel. Web, phone and WhatsApp orders share the same rules:
 * slot capacity, price snapshot and indivisible stock reservation.
 */
class OrderService
{
    public const CHANNELS = ['web', 'phone', 'whatsapp'];

    public function __construct(
        private readonly CartService $cart,
        private readonly Slots $slots,
        private readonly StockService $stock,
        private readonly OrderWorkflow $workflow,
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

            $order = $this->place($items, $data, 'web', customer: $user, actor: $user);
            $this->cart->clear($user);

            return $order;
        });
    }

    /**
     * Register an order taken by an employee (phone or WhatsApp). The customer is linked
     * by phone number when they have an account, so it shows in their order history.
     *
     * @param  array<int, float>  $lines  product_id => quantity
     *
     * @throws CartException
     */
    public function placeForStaff(string $channel, array $lines, array $data, User $employee): Order
    {
        $products = Product::with('stock')->whereIn('id', array_keys($lines))->get()->keyBy('id');

        $items = collect($lines)->map(function ($quantity, $productId) use ($products) {
            $product = $products[$productId] ?? throw new CartException('Producto no encontrado.');
            $this->cart->validateLine($product, (float) $quantity);

            return tap(new CartItem(['product_id' => $product->id, 'quantity' => (float) $quantity]))
                ->setRelation('product', $product);
        })->values();

        return DB::transaction(fn () => $this->place(
            $items, $data, $channel,
            customer: User::findByPhone($data['contact_phone']),
            actor: $employee,
        ));
    }

    /**
     * Open orders from the same phone for the same day and slot: probably the same
     * request received twice (e.g. a WhatsApp message and a call).
     *
     * @return Collection<int, Order>
     */
    public function possibleDuplicates(string $phone, string $slotDate, string $slot): Collection
    {
        $digits = User::normalizePhone($phone);

        return Order::whereDate('slot_date', $slotDate)
            ->where('slot', $slot)
            ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::Delivered])
            ->get()
            ->filter(fn (Order $order) => User::normalizePhone($order->contact_phone) === $digits)
            ->values();
    }

    /**
     * @param  Collection<int, CartItem>  $items
     *
     * @throws CartException
     */
    protected function place(Collection $items, array $data, string $channel, ?User $customer, User $actor): Order
    {
        if ($items->isEmpty()) {
            throw new CartException($channel === 'web' ? 'Tu carrito está vacío.' : 'Añade al menos un producto al pedido.');
        }

        $fulfillment = Fulfillment::from($data['fulfillment']);
        $day = CarbonImmutable::parse($data['slot_date']);

        if (! $this->slots->hasRoom($day, $data['slot'])) {
            throw new CartException('La franja elegida ya está completa. Elige otra, por favor.');
        }

        $totals = CartTotals::for($items, $fulfillment);

        $order = Order::create([
            'user_id' => $customer?->id,
            'created_by' => $actor->is($customer) ? null : $actor->id,
            'channel' => $channel,
            'source_reference' => $data['source_reference'] ?? null,
            'source_message' => $data['source_message'] ?? null,
            'status' => OrderStatus::Pending,
            'fulfillment' => $fulfillment,
            'payment_method' => $data['payment_method'] ?? 'cash',
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

        // Same transaction: if any line can't be reserved the order is rolled back entirely.
        $this->stock->reserve(
            $items->mapWithKeys(fn (CartItem $item) => [$item->product_id => $item->quantity])->all(),
            $actor,
            $order->code,
        );

        $order->refresh();
        $this->workflow->recordCreation($order, $actor);
        AuditLog::record('order', "Pedido {$order->code} confirmado ({$order->channelLabel()}, {$order->fulfillment->label()}, ".number_format((float) $order->total, 2, ',', '.').' €)', $order, user: $actor);

        return $order;
    }
}
