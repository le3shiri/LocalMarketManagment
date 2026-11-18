<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_browse_the_catalog_with_prices(): void
    {
        $product = Product::factory()->byWeight()->create(['name' => 'Tomate de ensalada', 'price' => 2.40]);
        $product->stock->update(['on_hand' => 20, 'store_qty' => 14, 'online_qty' => 6]);

        $this->get('/catalogo')
            ->assertOk()
            ->assertSee('Tomate de ensalada')
            ->assertSee('2,40')
            ->assertSee('/ kg')
            ->assertSee('Disponible');
    }

    public function test_withdrawn_products_are_not_listed_or_reachable(): void
    {
        $hidden = Product::factory()->inactive()->create(['name' => 'Producto retirado']);

        $this->get('/catalogo')->assertDontSee('Producto retirado');
        $this->get("/catalogo/{$hidden->slug}")->assertNotFound();
    }

    public function test_catalog_can_be_filtered_by_category(): void
    {
        $fruit = Category::factory()->create(['name' => 'Fruta']);
        Product::factory()->for($fruit)->create(['name' => 'Manzana golden']);
        Product::factory()->create(['name' => 'Leche entera']);

        $this->get("/catalogo?categoria={$fruit->slug}")
            ->assertOk()
            ->assertSee('Manzana golden')
            ->assertDontSee('Leche entera');
    }

    public function test_product_page_shows_details_and_weight_notice(): void
    {
        $product = Product::factory()->byWeight()->create(['name' => 'Pechuga de pollo', 'description' => 'Pollo de corral']);

        $this->get("/catalogo/{$product->slug}")
            ->assertOk()
            ->assertSee('Pechuga de pollo')
            ->assertSee('Pollo de corral')
            ->assertSee('importe es aproximado');
    }

    public function test_demo_catalog_seeder_is_idempotent(): void
    {
        $this->seed(CatalogSeeder::class);
        $count = Product::count();
        $this->seed(CatalogSeeder::class);

        $this->assertGreaterThan(15, $count);
        $this->assertSame($count, Product::count());
    }
}
