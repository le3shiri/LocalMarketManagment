<?php

namespace Tests\Feature;

use App\Livewire\ProductCatalog;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_page_contains_the_search_component(): void
    {
        $this->get('/catalogo')->assertOk()->assertSeeLivewire(ProductCatalog::class);
    }

    public function test_search_filters_by_name_as_you_type(): void
    {
        Product::factory()->create(['name' => 'Tomate de ensalada']);
        Product::factory()->create(['name' => 'Leche entera 1 L']);

        Livewire::test(ProductCatalog::class)
            ->set('search', 'tomate')
            ->assertSee('Tomate de ensalada')
            ->assertDontSee('Leche entera 1 L');
    }

    public function test_search_also_matches_the_description(): void
    {
        Product::factory()->create(['name' => 'Pan de payés', 'description' => 'Hogaza de masa madre']);

        Livewire::test(ProductCatalog::class)
            ->set('search', 'masa madre')
            ->assertSee('Pan de payés');
    }

    public function test_search_and_category_filter_can_be_combined(): void
    {
        $fruit = Category::factory()->create(['name' => 'Fruta']);
        $dairy = Category::factory()->create(['name' => 'Lácteos']);
        Product::factory()->for($fruit)->create(['name' => 'Manzana golden']);
        Product::factory()->for($dairy)->create(['name' => 'Yogur de manzana']);

        Livewire::test(ProductCatalog::class)
            ->set('search', 'manzana')
            ->call('selectCategory', $fruit->slug)
            ->assertSee('Manzana golden')
            ->assertDontSee('Yogur de manzana');
    }

    public function test_filters_are_read_from_the_query_string(): void
    {
        Product::factory()->create(['name' => 'Arroz bomba']);
        Product::factory()->create(['name' => 'Garbanzos']);

        Livewire::withQueryParams(['q' => 'arroz'])
            ->test(ProductCatalog::class)
            ->assertSet('search', 'arroz')
            ->assertSee('Arroz bomba')
            ->assertDontSee('Garbanzos');
    }

    public function test_empty_results_show_a_helpful_message(): void
    {
        Livewire::test(ProductCatalog::class)
            ->set('search', 'caviar')
            ->assertSee('No hemos encontrado productos para «caviar»')
            ->call('clearFilters')
            ->assertSet('search', '');
    }

    public function test_withdrawn_products_never_appear_in_search(): void
    {
        Product::factory()->inactive()->create(['name' => 'Tomate retirado']);

        Livewire::test(ProductCatalog::class)->set('search', 'tomate')->assertDontSee('Tomate retirado');
    }
}
