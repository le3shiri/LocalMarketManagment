<?php

namespace Tests\Feature\Warehouse;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private function warehouseUser(): User
    {
        return User::factory()->withRole(Role::WAREHOUSE)->create();
    }

    public function test_every_new_product_gets_an_empty_stock_row(): void
    {
        $product = Product::factory()->create();

        $this->assertDatabaseHas('product_stocks', ['product_id' => $product->id, 'on_hand' => 0]);
    }

    public function test_available_online_is_online_stock_minus_reservations(): void
    {
        $product = Product::factory()->byWeight()->create();
        $product->stock->update(['on_hand' => 20, 'store_qty' => 14, 'online_qty' => 6, 'reserved_qty' => 1]);

        $this->assertSame(5.0, $product->stock->refresh()->available_online);
    }

    public function test_warehouse_manager_sees_all_stock_figures(): void
    {
        $product = Product::factory()->byWeight()->create(['name' => 'Tomate de ensalada']);
        $product->stock->update(['on_hand' => 20, 'store_qty' => 14, 'online_qty' => 6, 'reserved_qty' => 1, 'min_alert' => 3]);

        $this->actingAs($this->warehouseUser())
            ->get('/almacen/inventario')
            ->assertOk()
            ->assertSeeInOrder(['Tomate de ensalada', '20 kg', '14 kg', '6 kg', '1 kg', '5 kg'])
            ->assertSeeHtml('name="min_alert" inputmode="decimal" value="3"'); // editable minimum (SUP-50)
    }

    public function test_low_stock_filter_only_lists_products_below_their_minimum(): void
    {
        $low = Product::factory()->create(['name' => 'Pechuga de pollo']);
        $low->stock->update(['on_hand' => 2, 'min_alert' => 3]);
        $ok = Product::factory()->create(['name' => 'Arroz bomba']);
        $ok->stock->update(['on_hand' => 40, 'min_alert' => 8]);

        $this->actingAs($this->warehouseUser())
            ->get('/almacen/inventario?low=1')
            ->assertSee('Pechuga de pollo')
            ->assertSee('Bajo mínimo')
            ->assertDontSee('Arroz bomba');
    }

    public function test_cashiers_can_consult_the_inventory_but_customers_can_not(): void
    {
        $this->actingAs(User::factory()->withRole(Role::CASHIER)->create())->get('/almacen/inventario')->assertOk();
        $this->actingAs(User::factory()->create())->get('/almacen/inventario')->assertForbidden();
    }
}
