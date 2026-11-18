<?php

namespace Tests\Feature\Cart;

use App\Livewire\AddToCart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AddToCartTest extends TestCase
{
    use RefreshDatabase;

    private function stocked(Product $product, float $online = 50): Product
    {
        $product->stock->update(['on_hand' => $online, 'online_qty' => $online]);

        return $product->refresh();
    }

    public function test_customer_can_add_a_product_to_the_cart(): void
    {
        $customer = User::factory()->create();
        $product = $this->stocked(Product::factory()->create());

        Livewire::actingAs($customer)
            ->test(AddToCart::class, ['product' => $product])
            ->set('quantity', 3)
            ->call('add')
            ->assertSet('success', true)
            ->assertSee('Añadido al carrito')
            ->assertDispatched('cart-updated');

        $this->assertSame(3.0, $customer->cartItems()->sole()->quantity);
    }

    public function test_adding_the_same_product_again_merges_the_line(): void
    {
        $customer = User::factory()->create();
        $product = $this->stocked(Product::factory()->create());

        $component = Livewire::actingAs($customer)->test(AddToCart::class, ['product' => $product]);
        $component->set('quantity', 2)->call('add');
        $component->set('quantity', 1)->call('add');

        $this->assertSame(1, CartItem::count());
        $this->assertSame(3.0, CartItem::sole()->quantity);
    }

    public function test_weighted_products_are_added_in_quarter_kilo_steps(): void
    {
        $customer = User::factory()->create();
        $product = $this->stocked(Product::factory()->byWeight()->create());

        Livewire::actingAs($customer)->test(AddToCart::class, ['product' => $product])
            ->assertSet('quantity', 0.25)
            ->set('quantity', 0.75)->call('add')
            ->assertSet('success', true);

        Livewire::actingAs($customer)->test(AddToCart::class, ['product' => $product])
            ->set('quantity', 0.3)->call('add')
            ->assertSet('success', false)
            ->assertSee('múltiplos de 0,25 kg');
    }

    public function test_guests_are_sent_to_login(): void
    {
        $product = $this->stocked(Product::factory()->create());

        Livewire::test(AddToCart::class, ['product' => $product])
            ->call('add')
            ->assertRedirect(route('login'));
    }

    public function test_staff_without_cart_permission_can_not_buy(): void
    {
        $product = $this->stocked(Product::factory()->create());

        Livewire::actingAs(User::factory()->withRole(Role::WAREHOUSE)->create())
            ->test(AddToCart::class, ['product' => $product])
            ->call('add')
            ->assertForbidden();
    }

    public function test_sold_out_products_show_a_disabled_button(): void
    {
        $product = Product::factory()->create(); // no stock

        Livewire::test(AddToCart::class, ['product' => $product])->assertSee('Agotado');
    }

    public function test_cart_page_lists_the_lines_and_subtotal(): void
    {
        $customer = User::factory()->create();
        $product = $this->stocked(Product::factory()->create(['name' => 'Arroz bomba', 'price' => 3.75]));
        $customer->cartItems()->create(['product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($customer)->get('/carrito')
            ->assertOk()
            ->assertSee('Arroz bomba')
            ->assertSee('7,50');
    }

    public function test_cart_survives_logging_out_and_in_again(): void
    {
        $customer = User::factory()->create();
        $product = $this->stocked(Product::factory()->create());
        $customer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($customer)->post('/logout');
        $this->post('/login', ['email' => $customer->email, 'password' => 'password']);

        $this->get('/carrito')->assertSee($product->name);
    }
}
