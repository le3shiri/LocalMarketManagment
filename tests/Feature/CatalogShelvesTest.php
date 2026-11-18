<?php

namespace Tests\Feature;

use App\Livewire\AddToCart;
use App\Livewire\ProductCatalog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogShelvesTest extends TestCase
{
    use RefreshDatabase;

    private function stocked(Product $product, float $online = 50): Product
    {
        $product->stock->update(['on_hand' => $online, 'online_qty' => $online]);

        return $product->refresh();
    }

    public function test_the_catalog_shows_every_section_as_a_shelf_with_all_its_products(): void
    {
        $fruit = Category::factory()->create(['name' => 'Fruta y verdura']);
        $bakery = Category::factory()->create(['name' => 'Panadería']);
        Product::factory()->count(30)->for($fruit)->create();
        Product::factory()->for($bakery)->create(['name' => 'Barra de pan']);

        $component = Livewire::test(ProductCatalog::class)
            ->assertSee('Pasa por las estanterías')
            ->assertSeeHtml('id="estante-fruta-y-verdura"')
            ->assertSeeHtml('id="estante-panaderia"')
            ->assertSee('30 productos')
            ->assertSee('Barra de pan');

        // Shelves are not paginated: all 31 products (30 fruit + the bread) are on the page.
        $this->assertSame(31, substr_count($component->html(), 'wire:key="product-'));
    }

    public function test_choosing_a_section_shows_only_that_shelf(): void
    {
        $fruit = Category::factory()->create(['name' => 'Fruta y verdura']);
        $bakery = Category::factory()->create(['name' => 'Panadería']);
        Product::factory()->for($fruit)->create(['name' => 'Manzana golden']);
        Product::factory()->for($bakery)->create(['name' => 'Barra de pan']);

        Livewire::test(ProductCatalog::class)
            ->call('selectCategory', $fruit->slug)
            ->assertSeeHtml('id="estante-fruta-y-verdura"')
            ->assertSee('Manzana golden')
            ->assertDontSee('Barra de pan');
    }

    public function test_search_results_are_a_plain_grid_with_a_count_and_section_tags(): void
    {
        $meat = Category::factory()->create(['name' => 'Carnicería']);
        Product::factory()->for($meat)->create(['name' => 'Pechuga de pollo']);
        Product::factory()->for($meat)->create(['name' => 'Muslos de pollo']);
        Product::factory()->for($meat)->create(['name' => 'Carne picada mixta']);

        Livewire::test(ProductCatalog::class)
            ->set('search', 'pollo')
            ->assertSee('2 resultados para «pollo»')
            ->assertSee('Volver a las estanterías')
            ->assertDontSeeHtml('id="estante-')
            ->assertDontSee('Carne picada mixta');
    }

    public function test_every_card_has_an_image_preview_photo_or_placeholder(): void
    {
        $fruit = Category::factory()->create(['name' => 'Fruta y verdura']);
        Product::factory()->for($fruit)->create(['name' => 'Tomate de ensalada', 'image_path' => 'products/tomate.jpg']);
        Product::factory()->for($fruit)->create(['name' => 'Manzana golden', 'image_path' => null]);

        $html = Livewire::test(ProductCatalog::class)->html();

        $this->assertStringContainsString('/storage/products/tomate.jpg', $html, 'Uploaded photo shown on the shelf');
        $this->assertSame(1, substr_count($html, 'data-placeholder'), 'Products without a photo get a placeholder');
    }

    public function test_the_unit_stepper_never_goes_below_one(): void
    {
        $product = $this->stocked(Product::factory()->create());

        Livewire::test(AddToCart::class, ['product' => $product, 'compact' => true])
            ->call('increment')->call('increment')
            ->assertSet('quantity', 3.0)
            ->call('decrement')->call('decrement')->call('decrement')
            ->assertSet('quantity', 1.0)
            ->assertSee('1 unidad');
    }

    public function test_weighed_products_offer_the_four_weight_chips(): void
    {
        $product = $this->stocked(Product::factory()->byWeight()->create(['name' => 'Tomate de ensalada']));

        Livewire::test(AddToCart::class, ['product' => $product, 'compact' => true])
            ->assertSeeHtml('value="0.25"')
            ->assertSeeHtml('value="0.75"')
            ->assertSee('Cantidad de Tomate de ensalada');
    }

    public function test_the_added_confirmation_clears_when_the_amount_changes(): void
    {
        $customer = User::factory()->create();
        $product = $this->stocked(Product::factory()->byWeight()->create());

        Livewire::actingAs($customer)->test(AddToCart::class, ['product' => $product, 'compact' => true])
            ->set('quantity', 0.5)->call('add')
            ->assertSet('success', true)
            ->assertSee('Añadido · 0,5 kg')
            ->set('quantity', 1)
            ->assertSet('success', false)
            ->assertDontSee('Añadido · ');
    }
}
