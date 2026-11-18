<?php

namespace Tests\Feature\Orders;

use App\Enums\MovementType;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\OrderService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * SUP-48: stock stays consistent from confirmation to delivery or cancellation.
 */
class OrderStockLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-23 08:00'));
        $this->customer = User::factory()->create(['phone' => '600123456']);
        $this->cashier = User::factory()->withRole(Role::CASHIER)->create();
    }

    private function product(bool $weighted, float $online, float $price = 2.40): Product
    {
        $product = ($weighted ? Product::factory()->byWeight() : Product::factory())->create(['price' => $price]);
        $product->stock->update(['on_hand' => $online + 10, 'store_qty' => 10, 'online_qty' => $online]);

        return $product;
    }

    /** Place a real order through the checkout service and bring it to "ready". */
    private function readyOrder(array $lines): Order
    {
        foreach ($lines as [$product, $quantity]) {
            $this->customer->cartItems()->create(['product_id' => $product->id, 'quantity' => $quantity]);
        }

        $order = app(OrderService::class)->placeFromCart($this->customer, [
            'fulfillment' => 'pickup', 'contact_name' => 'Marta', 'contact_phone' => '600123456',
            'slot_date' => '2026-11-23', 'slot' => '17:00-18:00',
        ]);
        $order->forceFill(['status' => OrderStatus::Ready])->save();

        return $order;
    }

    private function deliver(Order $order, array $weights = [])
    {
        return $this->actingAs($this->cashier)->patch("/gestion/pedidos/{$order->code}/estado", [
            'status' => 'delivered', 'weights' => $weights,
        ]);
    }

    public function test_delivery_consumes_the_reservation_from_online_stock_and_shop(): void
    {
        $eggs = $this->product(false, 6);
        $order = $this->readyOrder([[$eggs, 2]]);

        $this->deliver($order)->assertSessionHas('status');

        $stock = $eggs->stock->refresh();
        $this->assertSame(0.0, $stock->reserved_qty);
        $this->assertSame(4.0, $stock->online_qty);
        $this->assertSame(14.0, $stock->on_hand);
        $this->assertSame(10.0, $stock->store_qty, 'Counter stock is untouched');
        $this->assertSame(1, StockMovement::where('type', MovementType::Delivery)->count());
    }

    public function test_weighted_product_delivered_with_a_different_weight_keeps_stock_consistent(): void
    {
        // Proposal example: 1 kg of tomatoes ordered, 1,10 kg on the scale.
        $tomatoes = $this->product(true, 6, 2.40);
        $order = $this->readyOrder([[$tomatoes, 1]]);
        $item = $order->items()->sole();

        $this->deliver($order, [$item->id => '1.1'])->assertSessionHasNoErrors();

        $stock = $tomatoes->stock->refresh();
        $this->assertSame(0.0, $stock->reserved_qty);
        $this->assertSame(4.9, $stock->online_qty);
        $this->assertSame(14.9, $stock->on_hand);

        $item->refresh();
        $this->assertSame(1.1, $item->delivered_quantity);
        $this->assertSame('2.64', $item->line_total);
        $this->assertSame('2.64', $order->refresh()->total, 'Charged by real weight');

        $movement = StockMovement::where('type', MovementType::Delivery)->sole();
        $this->assertSame('Peso real 1,1 kg (pedido 1 kg)', $movement->reason);
    }

    public function test_extra_weight_can_not_take_stock_promised_to_other_orders(): void
    {
        $tomatoes = $this->product(true, 1);
        $order = $this->readyOrder([[$tomatoes, 1]]);

        $this->deliver($order, [$order->items()->sole()->id => '1.5'])->assertSessionHas('error');

        $this->assertSame(OrderStatus::Ready, $order->refresh()->status);
        $this->assertSame(1.0, $tomatoes->stock->refresh()->reserved_qty, 'Nothing changed');
    }

    public function test_weights_are_ignored_for_products_sold_by_unit(): void
    {
        $eggs = $this->product(false, 6);
        $order = $this->readyOrder([[$eggs, 2]]);

        $this->deliver($order, [$order->items()->sole()->id => '5']);

        $this->assertSame(2.0, $order->items()->sole()->delivered_quantity);
    }

    public function test_release_gives_back_exactly_what_was_reserved(): void
    {
        $eggs = $this->product(false, 6);
        $order = $this->readyOrder([[$eggs, 2]]);

        app(StockService::class)->release([$eggs->id => 2], $this->cashier, $order->code, 'Cancelado');

        $stock = $eggs->stock->refresh();
        $this->assertSame(0.0, $stock->reserved_qty);
        $this->assertSame(6.0, $stock->available_online);
        $this->assertSame(1, StockMovement::where('type', MovementType::Release)->count());
    }

    public function test_release_never_leaves_reservations_negative(): void
    {
        $eggs = $this->product(false, 6);

        app(StockService::class)->release([$eggs->id => 3], null, 'X');

        $this->assertSame(0.0, $eggs->stock->refresh()->reserved_qty);
    }

    public function test_delivering_more_than_available_is_rejected_at_service_level(): void
    {
        $eggs = $this->product(false, 1);

        $this->expectException(InvalidArgumentException::class);
        app(StockService::class)->deliver([$eggs->id => ['reserved' => 0, 'delivered' => 2]], null, 'X');
    }
}
