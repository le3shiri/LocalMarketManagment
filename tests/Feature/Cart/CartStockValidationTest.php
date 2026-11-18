<?php

namespace Tests\Feature\Cart;

use App\Exceptions\CartException;
use App\Livewire\AddToCart;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CartStockValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create();
        $this->product = Product::factory()->create(['name' => 'Huevos camperos']);
        // 10 in store, 5 online, 1 already reserved -> 4 available online.
        $this->product->stock->update(['on_hand' => 15, 'store_qty' => 10, 'online_qty' => 5, 'reserved_qty' => 1]);
    }

    public function test_can_not_add_more_than_the_available_online_stock(): void
    {
        Livewire::actingAs($this->customer)
            ->test(AddToCart::class, ['product' => $this->product])
            ->set('quantity', 5)
            ->call('add')
            ->assertSet('success', false)
            ->assertSee('Solo quedan 4 ud');

        $this->assertSame(0, $this->customer->cartItems()->count());
    }

    public function test_store_stock_is_never_counted_for_online_orders(): void
    {
        $this->expectException(CartException::class);

        app(CartService::class)->add($this->customer, $this->product, 10);
    }

    public function test_the_limit_takes_into_account_what_is_already_in_the_cart(): void
    {
        $cart = app(CartService::class);
        $cart->add($this->customer, $this->product, 3);

        try {
            $cart->add($this->customer, $this->product, 2);
            $this->fail('Expected CartException');
        } catch (CartException $e) {
            $this->assertStringContainsString('Ya tienes 3 ud en el carrito', $e->getMessage());
        }

        $this->assertSame(3.0, $this->customer->cartItems()->sole()->quantity);
    }

    public function test_exactly_the_available_quantity_is_accepted(): void
    {
        app(CartService::class)->add($this->customer, $this->product, 4);

        $this->assertSame(4.0, $this->customer->cartItems()->sole()->quantity);
    }

    public function test_cart_warns_when_stock_dropped_after_adding(): void
    {
        app(CartService::class)->add($this->customer, $this->product, 4);
        $this->product->stock->update(['reserved_qty' => 3]); // someone else ordered

        $this->actingAs($this->customer)->get('/carrito')->assertSee('Solo quedan 2 ud: ajusta la cantidad');
    }
}
