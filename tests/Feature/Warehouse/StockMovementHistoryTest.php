<?php

namespace Tests\Feature\Warehouse;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Product $rice;

    private Product $milk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->withRole(Role::WAREHOUSE)->create(['name' => 'Jordi']);
        $this->rice = Product::factory()->create(['name' => 'Arroz bomba']);
        $this->milk = Product::factory()->create(['name' => 'Leche entera']);

        $stock = app(StockService::class);
        $stock->registerEntry($this->rice, 30, 10, $this->manager, 'ALB-1');
        $stock->registerEntry($this->milk, 20, 5, $this->manager, 'ALB-2');
        $stock->reserve([$this->rice->id => 2], null, 'LM-000001');
    }

    public function test_history_lists_every_movement_with_deltas_and_responsible(): void
    {
        $this->actingAs($this->manager)->get('/almacen/movimientos')
            ->assertOk()
            ->assertSeeInOrder(['Reserva de pedido', 'LM-000001', 'Sistema', 'Entrada de mercancía', 'ALB-2', 'Jordi'])
            ->assertSee('+30 ud');
    }

    public function test_history_can_be_filtered_by_product_and_type(): void
    {
        $this->actingAs($this->manager)->get("/almacen/movimientos?product={$this->milk->id}")
            ->assertSee('ALB-2')->assertDontSee('ALB-1');

        $this->actingAs($this->manager)->get('/almacen/movimientos?type=reserve')
            ->assertSee('LM-000001')->assertDontSee('ALB-2');
    }

    public function test_history_can_be_filtered_by_responsible_and_date(): void
    {
        $this->actingAs($this->manager)->get("/almacen/movimientos?user={$this->manager->id}")->assertDontSee('LM-000001');
        $this->actingAs($this->manager)->get('/almacen/movimientos?from=2000-01-01&to=2000-01-02')->assertSee('No hay movimientos');
    }

    public function test_history_is_exported_as_csv_with_the_same_filters(): void
    {
        $response = $this->actingAs($this->manager)->get("/almacen/movimientos/exportar?product={$this->rice->id}");

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Fecha;Producto;SKU;Tipo', $csv);
        $this->assertStringContainsString('Arroz bomba', $csv);
        $this->assertStringContainsString('Reserva de pedido', $csv);
        $this->assertStringNotContainsString('Leche entera', $csv);
    }

    public function test_customers_can_not_see_the_ledger(): void
    {
        $this->actingAs(User::factory()->create())->get('/almacen/movimientos')->assertForbidden();
    }
}
