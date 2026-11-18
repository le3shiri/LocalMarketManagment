<?php

namespace Tests\Feature;

use App\Enums\Availability;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function productWithOnline(float $online, float $reserved = 0, string $name = 'Producto'): Product
    {
        $product = Product::factory()->create(['name' => $name]);
        $product->stock->update(['on_hand' => $online + 10, 'store_qty' => 10, 'online_qty' => $online, 'reserved_qty' => $reserved]);

        return $product->refresh();
    }

    public function test_availability_is_computed_from_online_stock_minus_reservations(): void
    {
        $this->assertSame(Availability::Available, $this->productWithOnline(10)->availability());
        $this->assertSame(Availability::Low, $this->productWithOnline(5, 2)->availability());
        $this->assertSame(Availability::SoldOut, $this->productWithOnline(4, 4)->availability());
    }

    public function test_store_stock_does_not_make_a_product_available_online(): void
    {
        $product = $this->productWithOnline(0);

        $this->assertSame(10.0, $product->stock->store_qty);
        $this->assertSame(Availability::SoldOut, $product->availability());
    }

    public function test_catalog_shows_each_availability_state(): void
    {
        $this->productWithOnline(20, name: 'Arroz bomba');
        $this->productWithOnline(2, name: 'Pan de payés');
        $this->productWithOnline(0, name: 'Pechuga de pollo');

        $this->get('/catalogo')
            ->assertSee('Disponible')
            ->assertSee('Últimas unidades')
            ->assertSee('Agotado');
    }

    public function test_product_page_tells_how_many_units_are_left_when_low(): void
    {
        $product = $this->productWithOnline(2);

        $this->get("/catalogo/{$product->slug}")->assertSee('quedan 2 ud');
    }
}
