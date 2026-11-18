<?php

namespace Tests\Feature\Checkout;

use App\Enums\Fulfillment;
use App\Enums\OrderStatus;
use App\Livewire\Checkout;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class PlaceOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-23 08:00')); // Monday morning
        config(['shop.delivery_fee' => 3.50, 'shop.free_delivery_from' => 50, 'shop.max_orders_per_slot' => 2]);

        $this->customer = User::factory()->create(['name' => 'Marta Puig', 'phone' => '600123456', 'address' => 'Carrer de Sants 12']);
        $this->product = Product::factory()->create(['name' => 'Arroz bomba', 'sku' => 'DES-001', 'price' => 3.75]);
        $this->product->stock->update(['on_hand' => 20, 'online_qty' => 10, 'store_qty' => 10]);
        $this->customer->cartItems()->create(['product_id' => $this->product->id, 'quantity' => 2]);
    }

    private function checkout()
    {
        return Livewire::actingAs($this->customer)->test(Checkout::class);
    }

    public function test_checkout_is_prefilled_with_the_customer_data(): void
    {
        $this->checkout()
            ->assertSet('contact_name', 'Marta Puig')
            ->assertSet('contact_phone', '600123456')
            ->assertSet('slot_date', '2026-11-23')
            ->assertSee('Recogida en tienda')
            ->assertSee('Envío a domicilio');
    }

    public function test_an_empty_cart_redirects_back_to_the_cart(): void
    {
        $this->customer->cartItems()->delete();

        $this->checkout()->assertRedirect(route('cart.index'));
    }

    public function test_pickup_order_is_persisted_with_a_correlative_code(): void
    {
        $this->checkout()
            ->set('slot', '17:00-18:00')
            ->call('placeOrder')
            ->assertRedirect(route('orders.show', 'LM-000001'));

        $order = Order::sole();
        $this->assertSame('LM-000001', $order->code);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(Fulfillment::Pickup, $order->fulfillment);
        $this->assertSame('2026-11-23', $order->slot_date->toDateString());
        $this->assertNull($order->delivery_address);
        $this->assertSame('7.50', $order->total);
        $this->assertSame(0, $this->customer->cartItems()->count(), 'Cart is emptied');
    }

    public function test_codes_are_correlative(): void
    {
        $this->checkout()->set('slot', '17:00-18:00')->call('placeOrder');
        $this->customer->cartItems()->create(['product_id' => $this->product->id, 'quantity' => 1]);
        $this->checkout()->set('slot', '18:00-19:00')->call('placeOrder');

        $this->assertSame(['LM-000001', 'LM-000002'], Order::orderBy('id')->pluck('code')->all());
    }

    public function test_delivery_requires_an_address_and_charges_the_fee(): void
    {
        $this->checkout()
            ->set('fulfillment', 'delivery')
            ->set('delivery_address', '')
            ->set('slot', '17:00-18:00')
            ->call('placeOrder')
            ->assertHasErrors('delivery_address');

        $this->checkout()
            ->set('fulfillment', 'delivery')
            ->set('delivery_address', 'Carrer de Sants 12, 3r 2a, 08014')
            ->set('slot', '17:00-18:00')
            ->call('placeOrder');

        $order = Order::sole();
        $this->assertSame('Carrer de Sants 12, 3r 2a, 08014', $order->delivery_address);
        $this->assertSame('3.50', $order->delivery_fee);
        $this->assertSame('11.00', $order->total);
    }

    public function test_order_lines_keep_a_snapshot_of_name_and_price(): void
    {
        $this->checkout()->set('slot', '17:00-18:00')->call('placeOrder');
        $this->product->update(['name' => 'Arroz renombrado', 'price' => 9.99]);

        $item = Order::sole()->items()->sole();
        $this->assertSame('Arroz bomba', $item->product_name);
        $this->assertSame('DES-001', $item->sku);
        $this->assertSame('3.75', $item->unit_price);
        $this->assertSame('7.50', $item->line_total);
    }

    public function test_a_full_slot_can_not_be_chosen(): void
    {
        foreach (range(1, 2) as $i) {
            Order::create([
                'user_id' => $this->customer->id, 'fulfillment' => 'pickup', 'contact_name' => 'X', 'contact_phone' => '600000000',
                'slot_date' => '2026-11-23', 'slot' => '17:00-18:00', 'subtotal' => 1, 'total' => 1,
            ]);
        }

        $this->checkout()
            ->assertSee('Completa')
            ->set('slot', '17:00-18:00')
            ->call('placeOrder')
            ->assertSet('error', 'La franja elegida ya está completa. Elige otra, por favor.');

        $this->assertSame(1, $this->customer->cartItems()->count());
    }

    public function test_past_slots_of_today_are_not_offered(): void
    {
        $this->travelTo(Carbon::parse('2026-11-23 12:30'));

        $this->checkout()
            ->assertDontSee('10:00-11:00')
            ->assertSee('17:00-18:00');
    }

    public function test_customers_only_see_their_own_orders(): void
    {
        $this->checkout()->set('slot', '17:00-18:00')->call('placeOrder');

        $this->actingAs($this->customer)->get('/pedidos/LM-000001')->assertOk()->assertSee('Arroz bomba');
        $this->actingAs(User::factory()->create())->get('/pedidos/LM-000001')->assertNotFound();
    }
}
