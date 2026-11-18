<?php

namespace Tests\Feature\Checkout;

use App\Enums\MovementType;
use App\Exceptions\InsufficientStockException;
use App\Livewire\Checkout;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\OrderService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Pruebas imprescindibles" from section 5 of the proposal.
 */
class StockReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-23 08:00'));
    }

    private function product(float $online, string $name = 'Huevos camperos'): Product
    {
        $product = Product::factory()->create(['name' => $name, 'price' => 2]);
        $product->stock->update(['on_hand' => $online + 5, 'store_qty' => 5, 'online_qty' => $online]);

        return $product->refresh();
    }

    private function customerWith(Product $product, float $quantity): User
    {
        $customer = User::factory()->create(['phone' => '600123456']);
        $customer->cartItems()->create(['product_id' => $product->id, 'quantity' => $quantity]);

        return $customer;
    }

    private function place(User $customer): Order
    {
        return app(OrderService::class)->placeFromCart($customer, [
            'fulfillment' => 'pickup', 'contact_name' => $customer->name, 'contact_phone' => '600123456',
            'slot_date' => '2026-11-23', 'slot' => '17:00-18:00',
        ]);
    }

    public function test_confirming_an_order_reserves_its_online_stock(): void
    {
        $product = $this->product(6);

        $order = $this->place($this->customerWith($product, 1));

        $stock = $product->stock->refresh();
        $this->assertSame(1.0, $stock->reserved_qty);
        $this->assertSame(5.0, $stock->available_online);
        $this->assertSame(6.0, $stock->online_qty, 'Units leave the shelf only on delivery');

        $movement = StockMovement::where('type', MovementType::Reserve)->sole();
        $this->assertSame($order->code, $movement->reference);
    }

    public function test_two_customers_ordering_the_last_unit_only_one_gets_it(): void
    {
        $product = $this->product(1);
        $first = $this->customerWith($product, 1);
        $second = $this->customerWith($product, 1);

        $this->place($first);

        try {
            $this->place($second);
            $this->fail('The second order must be rejected');
        } catch (InsufficientStockException $e) {
            $this->assertStringContainsString('«Huevos camperos»: agotado', $e->getMessage());
        }

        $this->assertSame(1, Order::count());
        $this->assertSame(1.0, $product->stock->refresh()->reserved_qty);
        $this->assertSame(1, $second->cartItems()->count(), 'The loser keeps their cart');
    }

    public function test_reservation_is_all_or_nothing_across_lines(): void
    {
        $plenty = $this->product(10, 'Arroz bomba');
        $scarce = $this->product(1, 'Pan de payés');
        $customer = $this->customerWith($plenty, 3);
        $customer->cartItems()->create(['product_id' => $scarce->id, 'quantity' => 2]);

        try {
            $this->place($customer);
            $this->fail('Expected InsufficientStockException');
        } catch (InsufficientStockException $e) {
            $this->assertSame([$scarce->id => 1.0], $e->shortages);
        }

        $this->assertSame(0.0, $plenty->stock->refresh()->reserved_qty, 'Nothing reserved for the line that had stock');
        $this->assertSame(0, Order::count());
        $this->assertSame(0, StockMovement::count());
    }

    public function test_checkout_blocks_confirmation_when_stock_changed_meanwhile(): void
    {
        $product = $this->product(3);
        $customer = $this->customerWith($product, 3);

        $component = Livewire::actingAs($customer)->test(Checkout::class)->set('slot', '17:00-18:00');

        // While the customer fills in the form, the shop reserves 2 units for a phone order.
        app(StockService::class)->reserve([$product->id => 2], null, 'TEL-1');

        $component->call('placeOrder')
            ->assertNoRedirect()
            ->assertSee('El stock ha cambiado mientras completabas el pedido')
            ->assertSee('solo quedan 1 ud');

        $this->assertSame(0, Order::count());
    }

    public function test_reservations_never_leave_available_stock_negative(): void
    {
        $product = $this->product(2);

        $this->expectException(InsufficientStockException::class);
        app(StockService::class)->reserve([$product->id => 2.5], null, 'X');

        $this->assertGreaterThanOrEqual(0, $product->stock->refresh()->available_online);
    }

    public function test_withdrawn_products_can_not_be_reserved(): void
    {
        $product = $this->product(5);
        $customer = $this->customerWith($product, 1);
        $product->forceFill(['is_active' => false])->save();

        $this->expectException(InsufficientStockException::class);
        $this->place($customer);
    }
}
