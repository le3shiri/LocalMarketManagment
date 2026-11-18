<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepeatOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Order $order;

    private Product $rice;

    private Product $tomato;

    private Product $bread;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create();
        $this->rice = $this->product('Arroz bomba', online: 10, price: 3.75);
        $this->tomato = $this->product('Tomate', online: 0.6, weighted: true);
        $this->bread = $this->product('Pan de payés', online: 0);

        $this->order = Order::factory()->for($this->customer)->create();
        foreach ([[$this->rice, 2, 3.50], [$this->tomato, 1.5, 2.40], [$this->bread, 1, 3.20]] as [$product, $qty, $oldPrice]) {
            $this->order->items()->create([
                'product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku,
                'unit_type' => $product->unit_type, 'unit_price' => $oldPrice, 'quantity' => $qty, 'line_total' => $oldPrice * $qty,
            ]);
        }
    }

    private function product(string $name, float $online, float $price = 2, bool $weighted = false): Product
    {
        $product = ($weighted ? Product::factory()->byWeight() : Product::factory())->create(['name' => $name, 'price' => $price]);
        $product->stock->update(['on_hand' => $online + 5, 'store_qty' => 5, 'online_qty' => $online]);

        return $product;
    }

    public function test_repeating_puts_the_products_back_in_the_cart_adjusted_to_stock(): void
    {
        $this->actingAs($this->customer)->post("/pedidos/{$this->order->code}/repetir")
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('status');

        $cart = $this->customer->cartItems()->pluck('quantity', 'product_id');
        $this->assertSame(2.0, $cart[$this->rice->id]);
        $this->assertSame(0.5, $cart[$this->tomato->id], 'Only 0,6 kg online -> 0,5 kg (multiple of 0,25)');
        $this->assertArrayNotHasKey($this->bread->id, $cart->all(), 'Sold out');
    }

    public function test_cart_shows_what_was_adjusted_or_skipped(): void
    {
        $this->actingAs($this->customer)->followingRedirects()->post("/pedidos/{$this->order->code}/repetir")
            ->assertSeeHtml('data-repeat-report')
            ->assertSee('Tomate: 0,5 kg de 1,5 kg')
            ->assertSee('Pan de payés (agotado)')
            ->assertSee('precios de hoy');
    }

    public function test_current_prices_are_used_not_the_old_ones(): void
    {
        $this->actingAs($this->customer)->post("/pedidos/{$this->order->code}/repetir");

        // Old order paid 3,50 €; the cart uses today's 3,75 €.
        $line = $this->customer->cartItems()->where('product_id', $this->rice->id)->with('product')->first();
        $this->assertSame(7.5, $line->line_total);
    }

    public function test_withdrawn_products_are_skipped_and_existing_cart_lines_are_respected(): void
    {
        $this->bread->forceFill(['is_active' => false])->save();
        $this->customer->cartItems()->create(['product_id' => $this->rice->id, 'quantity' => 9]);

        $this->actingAs($this->customer)->followingRedirects()->post("/pedidos/{$this->order->code}/repetir")
            ->assertSee('Pan de payés (ya no está a la venta)')
            ->assertSee('Arroz bomba: 1 ud de 2 ud');

        $this->assertSame(10.0, $this->customer->cartItems()->where('product_id', $this->rice->id)->value('quantity'));
    }

    public function test_nothing_available_returns_an_error(): void
    {
        $this->rice->stock->update(['online_qty' => 0]);
        $this->tomato->stock->update(['online_qty' => 0]);

        $this->actingAs($this->customer)->from("/pedidos/{$this->order->code}")->post("/pedidos/{$this->order->code}/repetir")
            ->assertRedirect("/pedidos/{$this->order->code}")
            ->assertSessionHas('error');
    }

    public function test_buttons_are_shown_and_only_own_orders_can_be_repeated(): void
    {
        $this->actingAs($this->customer)->get('/pedidos')->assertSee('Repetir pedido');
        $this->actingAs($this->customer)->get("/pedidos/{$this->order->code}")->assertSee('Repetir pedido');

        $this->actingAs(User::factory()->create())->post("/pedidos/{$this->order->code}/repetir")->assertNotFound();
    }
}
