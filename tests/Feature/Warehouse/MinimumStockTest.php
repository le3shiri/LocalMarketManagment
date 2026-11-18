<?php

namespace Tests\Feature\Warehouse;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MinimumStockTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->withRole(Role::WAREHOUSE)->create();
    }

    private function product(string $name, float $onHand, float $min): Product
    {
        $product = Product::factory()->create(['name' => $name]);
        $product->stock->update(['on_hand' => $onHand, 'store_qty' => $onHand, 'min_alert' => $min]);

        return $product;
    }

    public function test_manager_sets_the_minimum_from_the_inventory(): void
    {
        $product = $this->product('Arroz bomba', 40, 0);

        $this->actingAs($this->manager)
            ->patch("/almacen/productos/{$product->slug}/minimo", ['min_alert' => '8'])
            ->assertSessionHas('status');

        $this->assertSame(8.0, $product->stock->refresh()->min_alert);
        $this->assertDatabaseHas('audit_logs', ['action' => 'stock', 'description' => 'Mínimo de «Arroz bomba»: 0 ud → 8 ud']);
    }

    public function test_unit_products_need_whole_minimums_and_weighted_ones_accept_decimals(): void
    {
        $rice = $this->product('Arroz bomba', 40, 0);
        $tomatoes = Product::factory()->byWeight()->create(['name' => 'Tomate']);

        $this->actingAs($this->manager)->patch("/almacen/productos/{$rice->slug}/minimo", ['min_alert' => '8,5'])->assertSessionHasErrors('min_alert');
        $this->actingAs($this->manager)->patch("/almacen/productos/{$tomatoes->slug}/minimo", ['min_alert' => '2,5'])->assertSessionHasNoErrors();

        $this->assertSame(2.5, $tomatoes->stock->refresh()->min_alert);
    }

    public function test_minimum_can_not_be_negative(): void
    {
        $product = $this->product('Arroz bomba', 40, 5);

        $this->actingAs($this->manager)->patch("/almacen/productos/{$product->slug}/minimo", ['min_alert' => '-1'])->assertSessionHasErrors('min_alert');
        $this->assertSame(5.0, $product->stock->refresh()->min_alert);
    }

    public function test_reorder_list_shows_missing_quantity_and_suggested_order_by_urgency(): void
    {
        $chicken = $this->product('Pechuga de pollo', 1, 4);  // 75 % missing
        $this->product('Leche entera', 7, 8);                 // 12 % missing
        $this->product('Arroz bomba', 40, 8);                 // fine

        $this->actingAs($this->manager)->get('/almacen/reposicion')
            ->assertOk()
            ->assertSeeInOrder(['Pechuga de pollo', '1 ud', '4 ud', '3 ud', '7 ud', 'Leche entera'])
            ->assertDontSee('Arroz bomba')
            ->assertSeeHtml("data-suggested=\"{$chicken->id}\">7 ud");
    }

    public function test_navigation_shows_how_many_products_are_below_minimum(): void
    {
        $this->product('Pechuga de pollo', 1, 4);
        $this->product('Leche entera', 7, 8);

        $this->actingAs($this->manager)->get('/almacen/inventario')->assertSeeHtml('data-low-stock>2<');
    }

    public function test_cashiers_see_the_list_but_can_not_change_minimums(): void
    {
        $product = $this->product('Arroz bomba', 40, 5);
        $cashier = User::factory()->withRole(Role::CASHIER)->create();

        $this->actingAs($cashier)->get('/almacen/reposicion')->assertOk();
        $this->actingAs($cashier)->patch("/almacen/productos/{$product->slug}/minimo", ['min_alert' => 1])->assertForbidden();
    }
}
