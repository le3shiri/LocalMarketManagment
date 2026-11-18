<?php

namespace Tests\Feature\Warehouse;

use App\Enums\MovementType;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockBatch;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BatchTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Product $yogurt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-20 09:00'));
        $this->manager = User::factory()->withRole(Role::WAREHOUSE)->create();
        $this->yogurt = Product::factory()->create(['name' => 'Yogur natural', 'is_perishable' => true]);
    }

    private function receive(int $quantity, string $lot, string $expires)
    {
        return $this->actingAs($this->manager)->post('/almacen/entradas', [
            'product_id' => $this->yogurt->id, 'store_qty' => $quantity, 'online_qty' => 0,
            'lot_code' => $lot, 'expires_at' => $expires,
        ]);
    }

    public function test_perishables_require_lot_and_expiry_date(): void
    {
        $this->actingAs($this->manager)->post('/almacen/entradas', ['product_id' => $this->yogurt->id, 'store_qty' => 5, 'online_qty' => 0])
            ->assertSessionHasErrors(['lot_code', 'expires_at']);

        $this->receive(5, 'L1', '2026-11-19')->assertSessionHasErrors('expires_at'); // already expired
    }

    public function test_each_receipt_creates_a_batch_with_its_quantity(): void
    {
        $this->receive(12, 'l-2611-a', '2026-11-28')->assertSessionHasNoErrors();

        $batch = StockBatch::sole();
        $this->assertSame('L-2611-A', $batch->lot_code);
        $this->assertSame('2026-11-28', $batch->expires_at->toDateString());
        $this->assertSame([12.0, 12.0], [$batch->quantity_received, $batch->quantity_remaining]);
        $this->assertTrue($batch->receiver->is($this->manager));
    }

    public function test_non_perishables_get_a_batch_without_expiry(): void
    {
        $rice = Product::factory()->create();

        $this->actingAs($this->manager)->post('/almacen/entradas', ['product_id' => $rice->id, 'store_qty' => 10, 'online_qty' => 0, 'reference' => 'ALB-9']);

        $batch = StockBatch::sole();
        $this->assertNull($batch->expires_at);
        $this->assertSame('ALB-9', $batch->lot_code);
    }

    public function test_units_leaving_the_shop_consume_the_first_expiring_batch_first(): void
    {
        $this->receive(5, 'LATE', '2026-11-30');
        $this->receive(4, 'EARLY', '2026-11-24');

        app(StockService::class)->removeStock($this->yogurt, 6, 'store', MovementType::Exit, $this->manager, 'Donación');

        $this->assertSame(0.0, StockBatch::firstWhere('lot_code', 'EARLY')->quantity_remaining);
        $this->assertSame(3.0, StockBatch::firstWhere('lot_code', 'LATE')->quantity_remaining);
    }

    public function test_transfers_between_channels_do_not_consume_batches(): void
    {
        $this->receive(5, 'L1', '2026-11-30');

        app(StockService::class)->transfer($this->yogurt, 2, 'online', $this->manager, 'Reparto');

        $this->assertSame(5.0, StockBatch::sole()->quantity_remaining);
    }

    public function test_batch_page_shows_lots_expiry_and_remaining(): void
    {
        $this->receive(5, 'LATE', '2026-11-30');
        $this->receive(4, 'EARLY', '2026-11-22');

        $this->actingAs($this->manager)->get("/almacen/productos/{$this->yogurt->slug}/lotes")
            ->assertOk()
            ->assertSeeInOrder(['EARLY', '22/11/2026', 'en 2 días', 'LATE', '30/11/2026']);
    }
}
