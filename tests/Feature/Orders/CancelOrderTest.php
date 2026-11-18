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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CancelOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $cashier;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-23 08:00'));
        $this->customer = User::factory()->create();
        $this->cashier = User::factory()->withRole(Role::CASHIER)->create();
        $this->product = Product::factory()->create();
        $this->product->stock->update(['on_hand' => 15, 'store_qty' => 10, 'online_qty' => 5]);
    }

    private function order(float $quantity = 2): Order
    {
        $this->customer->cartItems()->create(['product_id' => $this->product->id, 'quantity' => $quantity]);

        return app(OrderService::class)->placeFromCart($this->customer, [
            'fulfillment' => 'pickup', 'contact_name' => 'Marta', 'contact_phone' => '600123456',
            'slot_date' => '2026-11-23', 'slot' => '17:00-18:00',
        ]);
    }

    public function test_staff_cancel_releases_exactly_the_reserved_stock(): void
    {
        $order = $this->order(2);
        $this->assertSame(3.0, $this->product->stock->refresh()->available_online);

        $this->actingAs($this->cashier)
            ->post("/gestion/pedidos/{$order->code}/cancelar", ['reason' => 'La clienta ha llamado'])
            ->assertSessionHas('status');

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        $this->assertSame(5.0, $this->product->stock->refresh()->available_online);
        $this->assertSame('La clienta ha llamado', $order->statusChanges->last()->note);
        $this->assertSame(1, StockMovement::where('type', MovementType::Release)->count());
    }

    public function test_staff_must_give_a_reason(): void
    {
        $order = $this->order();

        $this->actingAs($this->cashier)->post("/gestion/pedidos/{$order->code}/cancelar", ['reason' => ''])->assertSessionHasErrors('reason');
        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);
    }

    public function test_orders_in_preparation_or_ready_can_still_be_cancelled_by_staff(): void
    {
        $order = $this->order();
        $order->forceFill(['status' => OrderStatus::Ready])->save();

        $this->actingAs($this->cashier)->post("/gestion/pedidos/{$order->code}/cancelar", ['reason' => 'No vino a recogerlo']);

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
    }

    public function test_delivered_or_cancelled_orders_can_not_be_cancelled_again(): void
    {
        $order = $this->order();
        $this->actingAs($this->cashier)->post("/gestion/pedidos/{$order->code}/cancelar", ['reason' => 'Primera vez']);

        $this->actingAs($this->cashier)
            ->post("/gestion/pedidos/{$order->code}/cancelar", ['reason' => 'Segunda vez'])
            ->assertSessionHas('error');

        $this->assertSame(5.0, $this->product->stock->refresh()->available_online, 'Stock released only once');
    }

    public function test_customer_can_cancel_their_own_pending_order(): void
    {
        $order = $this->order();

        $this->actingAs($this->customer)->post("/pedidos/{$order->code}/cancelar")->assertSessionHas('status');

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        $this->assertSame(5.0, $this->product->stock->refresh()->available_online);
    }

    public function test_customer_can_not_cancel_once_preparation_started(): void
    {
        $order = $this->order();
        $order->forceFill(['status' => OrderStatus::Preparing])->save();

        $this->actingAs($this->customer)
            ->post("/pedidos/{$order->code}/cancelar")
            ->assertSessionHas('error', 'Tu pedido ya se está preparando. Llama a la tienda para cancelarlo.');
    }

    public function test_customer_can_not_cancel_someone_elses_order(): void
    {
        $order = $this->order();

        $this->actingAs(User::factory()->create())->post("/pedidos/{$order->code}/cancelar")->assertNotFound();
        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);
    }
}
