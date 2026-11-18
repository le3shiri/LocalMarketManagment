<?php

namespace Tests\Feature\Warehouse;

use App\Models\Product;
use App\Models\Role;
use App\Models\StockBatch;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExpiringSoonTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-20 09:00'));
        config(['shop.expiry_warning_days' => 3]);
        $this->manager = User::factory()->withRole(Role::WAREHOUSE)->create();
    }

    private function batch(string $product, string $lot, string $expires, float $remaining = 5): StockBatch
    {
        return Product::factory()->create(['name' => $product, 'is_perishable' => true])
            ->batches()->create(['lot_code' => $lot, 'expires_at' => $expires, 'quantity_received' => 10, 'quantity_remaining' => $remaining]);
    }

    public function test_lists_batches_expiring_within_the_warning_window_soonest_first(): void
    {
        $this->batch('Yogur', 'L-YOG', '2026-11-22');
        $this->batch('Leche', 'L-LEC', '2026-11-20');
        $this->batch('Queso', 'L-QUE', '2026-11-30');      // outside window
        $this->batch('Pollo', 'L-POL', '2026-11-21', 0);   // nothing left

        $this->actingAs($this->manager)->get('/almacen/caducidades')
            ->assertOk()
            ->assertSeeInOrder(['Hoy', 'Leche', 'L-LEC', 'En 2 días', 'Yogur'])
            ->assertDontSee('L-QUE')
            ->assertDontSee('L-POL');
    }

    public function test_window_can_be_widened(): void
    {
        $this->batch('Queso', 'L-QUE', '2026-11-30');

        $this->actingAs($this->manager)->get('/almacen/caducidades?dias=14')->assertSee('L-QUE')->assertSeeHtml('data-days="10"');
    }

    public function test_already_expired_or_withdrawn_batches_are_not_in_this_list(): void
    {
        $this->batch('Yogur', 'L-OLD', '2026-11-19');
        $this->batch('Leche', 'L-WD', '2026-11-21')->update(['status' => StockBatch::WITHDRAWN]);

        $this->actingAs($this->manager)->get('/almacen/caducidades')->assertDontSee('L-OLD')->assertDontSee('L-WD');
    }

    public function test_demo_catalog_creates_batches_with_expiry_for_perishables(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->assertTrue(StockBatch::whereNotNull('expires_at')->exists());
        $this->assertFalse(StockBatch::whereHas('product', fn ($q) => $q->where('is_perishable', false))->whereNotNull('expires_at')->exists());
    }
}
