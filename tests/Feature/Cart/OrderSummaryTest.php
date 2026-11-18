<?php

namespace Tests\Feature\Cart;

use App\Enums\Fulfillment;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Support\CartTotals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderSummaryTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create();
        config(['shop.delivery_fee' => 3.50, 'shop.free_delivery_from' => 50]);
    }

    private function add(float $price, float $quantity, bool $weighted = false): void
    {
        $product = ($weighted ? Product::factory()->byWeight() : Product::factory())->create(['price' => $price]);
        $product->stock->update(['on_hand' => 100, 'online_qty' => 100]);
        $this->customer->cartItems()->create(['product_id' => $product->id, 'quantity' => $quantity]);
    }

    private function totals(Fulfillment $fulfillment): CartTotals
    {
        return CartTotals::for(app(CartService::class)->items($this->customer), $fulfillment);
    }

    public function test_line_totals_and_subtotal_are_computed(): void
    {
        $this->add(3.75, 2);        // 7,50
        $this->add(2.40, 1.25, true); // 3,00

        $this->assertSame(10.5, $this->totals(Fulfillment::Pickup)->subtotal);
    }

    public function test_pickup_is_free_and_delivery_has_a_fee_below_the_threshold(): void
    {
        $this->add(10, 2);

        $this->assertSame(20.0, $this->totals(Fulfillment::Pickup)->total);
        $delivery = $this->totals(Fulfillment::Delivery);
        $this->assertSame(3.5, $delivery->deliveryFee);
        $this->assertSame(23.5, $delivery->total);
        $this->assertSame(30.0, $delivery->missingForFreeDelivery);
    }

    public function test_delivery_is_free_from_the_threshold(): void
    {
        $this->add(25, 2);

        $this->assertSame(0.0, $this->totals(Fulfillment::Delivery)->deliveryFee);
    }

    public function test_cart_page_shows_the_breakdown_and_weight_notice(): void
    {
        $this->add(2.40, 1, true);

        $this->actingAs($this->customer)->get('/carrito')
            ->assertSee('Resumen del pedido')
            ->assertSee('Total (recogida)')
            ->assertSee('2,40')
            ->assertSee('Te faltan')
            ->assertSee('el importe es aproximado');
    }
}
