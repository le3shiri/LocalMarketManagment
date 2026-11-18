<?php

namespace Tests\Feature\Cart;

use App\Livewire\CartPage;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditCartTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create();
    }

    private function line(float $quantity, float $online = 10, bool $weighted = false, float $price = 2.00): CartItem
    {
        $factory = Product::factory();
        $product = ($weighted ? $factory->byWeight() : $factory)->create(['price' => $price]);
        $product->stock->update(['on_hand' => $online, 'online_qty' => $online]);

        return $this->customer->cartItems()->create(['product_id' => $product->id, 'quantity' => $quantity]);
    }

    public function test_quantity_can_be_increased_and_decreased_by_one_step(): void
    {
        $item = $this->line(2);

        Livewire::actingAs($this->customer)->test(CartPage::class)
            ->call('increment', $item->id)
            ->assertDispatched('cart-updated');
        $this->assertSame(3.0, $item->refresh()->quantity);

        Livewire::actingAs($this->customer)->test(CartPage::class)->call('decrement', $item->id);
        $this->assertSame(2.0, $item->refresh()->quantity);
    }

    public function test_weighted_lines_change_in_quarter_kilos(): void
    {
        $item = $this->line(1, weighted: true);

        Livewire::actingAs($this->customer)->test(CartPage::class)->call('increment', $item->id);

        $this->assertSame(1.25, $item->refresh()->quantity);
    }

    public function test_typing_a_quantity_updates_the_line_and_totals(): void
    {
        $item = $this->line(1, price: 3.75);

        Livewire::actingAs($this->customer)->test(CartPage::class)
            ->call('updateQuantity', $item->id, '4')
            ->assertSee('15,00');

        $this->assertSame(4.0, $item->refresh()->quantity);
    }

    public function test_increasing_beyond_the_online_stock_is_refused(): void
    {
        $item = $this->line(3, online: 3);

        Livewire::actingAs($this->customer)->test(CartPage::class)
            ->call('increment', $item->id)
            ->assertSee('Solo quedan 3 ud');

        $this->assertSame(3.0, $item->refresh()->quantity);
    }

    public function test_decreasing_is_allowed_even_if_stock_dropped(): void
    {
        $item = $this->line(5, online: 2);

        Livewire::actingAs($this->customer)->test(CartPage::class)->call('decrement', $item->id);

        $this->assertSame(4.0, $item->refresh()->quantity);
    }

    public function test_decrementing_the_last_unit_removes_the_line(): void
    {
        $item = $this->line(1);

        Livewire::actingAs($this->customer)->test(CartPage::class)->call('decrement', $item->id);

        $this->assertModelMissing($item);
    }

    public function test_a_line_can_be_removed_and_the_cart_emptied(): void
    {
        $first = $this->line(1);
        $this->line(2);

        Livewire::actingAs($this->customer)->test(CartPage::class)->call('remove', $first->id);
        $this->assertSame(1, $this->customer->cartItems()->count());

        Livewire::actingAs($this->customer)->test(CartPage::class)
            ->call('clear')
            ->assertSee('Tu carrito está vacío');
        $this->assertSame(0, $this->customer->cartItems()->count());
    }

    public function test_customers_can_not_touch_other_customers_lines(): void
    {
        $other = User::factory()->create();
        $product = Product::factory()->create();
        $foreign = $other->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);

        Livewire::actingAs($this->customer)->test(CartPage::class)->call('remove', $foreign->id);

        $this->assertModelExists($foreign);
    }
}
