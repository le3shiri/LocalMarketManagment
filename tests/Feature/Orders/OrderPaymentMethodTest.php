<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Livewire\Checkout;
use App\Livewire\Staff\CreateOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class OrderPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $cashier;

    private Product $tomatoes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-23 08:00'));
        $this->customer = User::factory()->create(['phone' => '600123456']);
        $this->cashier = User::factory()->withRole(Role::CASHIER)->create();
        $this->tomatoes = Product::factory()->byWeight()->create(['price' => 2.40]);
        $this->tomatoes->stock->update(['on_hand' => 20, 'store_qty' => 10, 'online_qty' => 10]);
        $this->customer->cartItems()->create(['product_id' => $this->tomatoes->id, 'quantity' => 1]);
    }

    public function test_customer_chooses_card_on_pickup_at_checkout(): void
    {
        Livewire::actingAs($this->customer)->test(Checkout::class)
            ->assertSee('¿Cómo vas a pagar?')
            ->assertSet('payment_method', 'cash')
            ->set('payment_method', 'card')
            ->set('slot', '17:00-18:00')
            ->call('placeOrder');

        $this->assertSame(PaymentMethod::Card, Order::sole()->payment_method);
        $this->actingAs($this->customer)->get('/pedidos/'.Order::sole()->code)->assertSee('Tarjeta al recogerlo');
    }

    public function test_unknown_payment_methods_are_rejected(): void
    {
        Livewire::actingAs($this->customer)->test(Checkout::class)
            ->set('payment_method', 'bitcoin')
            ->set('slot', '17:00-18:00')
            ->call('placeOrder')
            ->assertHasErrors('payment_method');
    }

    public function test_staff_record_the_payment_method_of_phone_orders(): void
    {
        Livewire::actingAs($this->cashier)->withQueryParams(['canal' => 'phone'])->test(CreateOrder::class)
            ->set('contact_phone', '611222333')->set('contact_name', 'Pere')
            ->call('addProduct', $this->tomatoes->id)
            ->set('payment_method', 'card')
            ->set('slot', '17:00-18:00')
            ->call('save');

        $this->assertSame(PaymentMethod::Card, Order::sole()->payment_method);
    }

    public function test_delivery_records_the_payment_with_the_final_amount(): void
    {
        Livewire::actingAs($this->customer)->test(Checkout::class)->set('slot', '17:00-18:00')->call('placeOrder');
        $order = Order::sole();
        $order->forceFill(['status' => OrderStatus::Ready])->save();

        $this->actingAs($this->cashier)->get("/gestion/pedidos/{$order->code}")->assertSee('Efectivo en la entrega')->assertSee('Cobrado con');

        $this->actingAs($this->cashier)->patch("/gestion/pedidos/{$order->code}/estado", [
            'status' => 'delivered',
            'weights' => [$order->items()->sole()->id => '1.25'],
            'payment_method' => 'card', // customer changed their mind at the counter
        ])->assertSessionHasNoErrors();

        $payment = $order->refresh()->payment;
        $this->assertSame(PaymentMethod::Card, $payment->method);
        $this->assertSame('3.00', $payment->amount, '2,40 €/kg × 1,25 kg');
        $this->assertTrue($payment->user->is($this->cashier));
        $this->assertSame(PaymentMethod::Card, $order->payment_method);

        $this->actingAs($this->cashier)->get("/gestion/pedidos/{$order->code}")->assertSee('Cobrado · Tarjeta');
    }

    public function test_cancelled_orders_are_never_charged(): void
    {
        Livewire::actingAs($this->customer)->test(Checkout::class)->set('slot', '17:00-18:00')->call('placeOrder');
        $order = Order::sole();

        $this->actingAs($this->cashier)->post("/gestion/pedidos/{$order->code}/cancelar", ['reason' => 'No viene']);

        $this->assertNull($order->refresh()->payment);
    }
}
