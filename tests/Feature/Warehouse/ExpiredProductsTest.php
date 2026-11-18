<?php

namespace Tests\Feature\Warehouse;

use App\Enums\Availability;
use App\Enums\MovementType;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

class ExpiredProductsTest extends TestCase
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
        // 6 in store, 4 online (1 reserved) = 10 units in two batches.
        $this->yogurt->stock->update(['on_hand' => 10, 'store_qty' => 6, 'online_qty' => 4, 'reserved_qty' => 1]);
        $this->yogurt->batches()->create(['lot_code' => 'OLD', 'expires_at' => '2026-11-19', 'quantity_received' => 8, 'quantity_remaining' => 8]);
        $this->yogurt->batches()->create(['lot_code' => 'NEW', 'expires_at' => '2026-11-30', 'quantity_received' => 2, 'quantity_remaining' => 2]);
    }

    public function test_nightly_job_blocks_expired_units_from_store_then_free_online_stock(): void
    {
        $this->artisan('stock:block-expired')->expectsOutputToContain('1 lote(s) caducado(s) bloqueados.')->assertSuccessful();

        $stock = $this->yogurt->stock->refresh();
        $this->assertSame(0.0, $stock->store_qty);   // 6 from store
        $this->assertSame(2.0, $stock->online_qty);  // 2 from free online (1 stays reserved)
        $this->assertSame(1.0, $stock->reserved_qty);
        $this->assertSame(2.0, $stock->on_hand);

        $this->assertSame(StockBatch::EXPIRED, StockBatch::firstWhere('lot_code', 'OLD')->status);
        $this->assertSame(StockBatch::ACTIVE, StockBatch::firstWhere('lot_code', 'NEW')->status);

        $movement = StockMovement::where('type', MovementType::Expiry)->sole();
        $this->assertStringContainsString('Lote OLD caducado el 19/11/2026', $movement->reason);
    }

    public function test_expired_units_can_no_longer_be_sold_online_or_at_the_till(): void
    {
        $this->artisan('stock:block-expired');

        $this->assertSame(1.0, $this->yogurt->stock->refresh()->available_online, 'Only the fresh batch remains sellable');
        $this->assertSame(Availability::Low, $this->yogurt->refresh()->availability());
        $this->assertSame(0.0, $this->yogurt->stock->store_qty, 'Nothing left for the till');
    }

    public function test_reserved_units_from_an_expired_batch_are_flagged_for_a_substitute(): void
    {
        $this->yogurt->stock->update(['reserved_qty' => 4]); // all online units promised
        $this->yogurt->batches()->where('lot_code', 'NEW')->delete();
        StockBatch::firstWhere('lot_code', 'OLD')->update(['quantity_remaining' => 10]);

        $this->artisan('stock:block-expired');

        $this->assertStringContainsString('reservado(s) en pedidos: ofrecer sustituto', StockMovement::where('type', MovementType::Expiry)->sole()->reason);
        $this->assertSame(4.0, $this->yogurt->stock->refresh()->online_qty, 'Reserved units untouched');
    }

    public function test_job_is_idempotent_and_scheduled_daily(): void
    {
        $this->artisan('stock:block-expired');
        $this->artisan('stock:block-expired')->expectsOutputToContain('No hay lotes caducados.');
        $this->assertSame(1, StockMovement::where('type', MovementType::Expiry)->count());

        $this->assertTrue(collect(Schedule::events())->contains(fn ($e) => str_contains($e->command, 'stock:block-expired') && $e->expression === '5 0 * * *'));
    }

    public function test_expired_batches_are_listed_and_their_withdrawal_confirmed(): void
    {
        $this->actingAs($this->manager)->get('/almacen/caducidades')
            ->assertSee('Caducados (1): no se pueden vender')
            ->assertSee('Bloquear caducados ahora');

        $this->actingAs($this->manager)->post('/almacen/caducidades/bloquear')->assertSessionHas('status');
        $batch = StockBatch::firstWhere('lot_code', 'OLD');

        $this->actingAs($this->manager)->patch("/almacen/lotes/{$batch->id}/retirada")->assertSessionHas('status');

        $this->assertSame([StockBatch::WITHDRAWN, 0.0], [$batch->refresh()->status, $batch->quantity_remaining]);
        $this->actingAs($this->manager)->get('/almacen/caducidades')->assertDontSee('Caducados (');
    }

    public function test_cashiers_can_not_block_or_withdraw(): void
    {
        $this->actingAs(User::factory()->withRole(Role::CASHIER)->create())->post('/almacen/caducidades/bloquear')->assertForbidden();
    }
}
