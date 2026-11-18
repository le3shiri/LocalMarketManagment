<?php

namespace Tests\Feature\Warehouse;

use App\Enums\MovementType;
use App\Enums\WriteOffType;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WriteOff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WriteOffTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Product $eggs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-20 10:00'));
        $this->manager = User::factory()->withRole(Role::WAREHOUSE)->create(['name' => 'Jordi']);
        $this->eggs = Product::factory()->create(['name' => 'Huevos (docena)', 'price' => 3.40, 'is_perishable' => true]);
        $this->eggs->stock->update(['on_hand' => 12, 'store_qty' => 10, 'online_qty' => 2]);
        $this->eggs->batches()->create(['lot_code' => 'A', 'expires_at' => '2026-11-25', 'quantity_received' => 6, 'quantity_remaining' => 6]);
        $this->eggs->batches()->create(['lot_code' => 'B', 'expires_at' => '2026-12-05', 'quantity_received' => 6, 'quantity_remaining' => 6]);
    }

    private function writeOff(array $data)
    {
        return $this->actingAs($this->manager)->post('/almacen/mermas', $data + [
            'product_id' => $this->eggs->id, 'channel' => 'store', 'type' => 'damaged',
        ]);
    }

    public function test_damaged_goods_are_written_off_valued_and_logged(): void
    {
        $this->writeOff(['quantity' => 2, 'notes' => 'Caja aplastada'])->assertSessionHas('status');

        $writeOff = WriteOff::sole();
        $this->assertSame(WriteOffType::Damaged, $writeOff->type);
        $this->assertSame('6.80', $writeOff->value);
        $this->assertSame(8.0, $this->eggs->stock->refresh()->store_qty);

        $movement = StockMovement::sole();
        $this->assertSame(MovementType::WriteOff, $movement->type);
        $this->assertSame('Merma: Dañado / roto · Caja aplastada', $movement->reason);
    }

    public function test_without_a_batch_the_first_expiring_lot_is_used(): void
    {
        $this->writeOff(['quantity' => 2]);

        $this->assertSame(4.0, StockBatch::firstWhere('lot_code', 'A')->quantity_remaining);
        $this->assertSame(6.0, StockBatch::firstWhere('lot_code', 'B')->quantity_remaining);
    }

    public function test_a_specific_batch_can_be_written_off(): void
    {
        $batchB = StockBatch::firstWhere('lot_code', 'B');

        $this->writeOff(['quantity' => 3, 'type' => 'recalled', 'stock_batch_id' => $batchB->id])->assertSessionHasNoErrors();

        $this->assertSame(3.0, $batchB->refresh()->quantity_remaining);
        $this->assertSame(6.0, StockBatch::firstWhere('lot_code', 'A')->quantity_remaining, 'FEFO not applied');
        $this->assertTrue(WriteOff::sole()->batch->is($batchB));
    }

    public function test_cannot_write_off_more_than_the_batch_or_the_stock(): void
    {
        $batchA = StockBatch::firstWhere('lot_code', 'A');

        $this->writeOff(['quantity' => 7, 'stock_batch_id' => $batchA->id])->assertSessionHasErrors(['quantity' => 'En el lote A solo quedan 6 ud.']);
        $this->writeOff(['quantity' => 11])->assertSessionHasErrors('quantity');
        $this->assertSame(0, WriteOff::count());
    }

    public function test_report_totals_losses_by_type_for_the_period(): void
    {
        $this->writeOff(['quantity' => 2]);                       // 6,80 damaged
        $this->writeOff(['quantity' => 1, 'type' => 'expired']);  // 3,40 expired

        $response = $this->actingAs($this->manager)->get('/almacen/mermas')
            ->assertOk()
            ->assertSeeHtml('data-writeoff-total>10,20')
            ->assertSeeInOrder(['Caducado', '3,40', 'Dañado / roto', '6,80'])->assertSee('Jordi');

        $this->assertSame(6.8, (float) $response->viewData('byType')['damaged']->value);

        $this->actingAs($this->manager)->get('/almacen/mermas?type=expired')->assertSeeHtml('data-writeoff-total>3,40');
    }

    public function test_cashiers_can_see_the_report_but_not_record_losses(): void
    {
        $cashier = User::factory()->withRole(Role::CASHIER)->create();

        $this->actingAs($cashier)->get('/almacen/mermas')->assertOk()->assertDontSee('Registrar merma');
        $this->actingAs($cashier)->post('/almacen/mermas', ['product_id' => $this->eggs->id, 'channel' => 'store', 'type' => 'damaged', 'quantity' => 1])->assertForbidden();
    }
}
