<?php

namespace Tests\Feature\Warehouse;

use App\Enums\MovementType;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Includes two "pruebas imprescindibles" from section 5 of the proposal.
 */
class StockCountAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Product $tomatoes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->withRole(Role::WAREHOUSE)->create(['name' => 'Jordi']);
        $this->tomatoes = Product::factory()->byWeight()->create(['name' => 'Tomate']);
        // Proposal example: 20 kg real, 14 store, 6 online, 1 reserved.
        $this->tomatoes->stock->update(['on_hand' => 20, 'store_qty' => 14, 'online_qty' => 6, 'reserved_qty' => 1]);
    }

    private function recount(string $counted, string $reason = 'Recuento de cierre')
    {
        return $this->actingAs($this->manager)->post('/almacen/recuentos', [
            'product_id' => $this->tomatoes->id, 'counted' => $counted, 'reason' => $reason,
        ]);
    }

    public function test_after_a_count_with_differences_the_ledger_keeps_reason_and_responsible(): void
    {
        $this->recount('18,5', 'Merma de tomates blandos')->assertSessionHas('status');

        $stock = $this->tomatoes->stock->refresh();
        $this->assertSame(18.5, $stock->on_hand);
        $this->assertSame(12.5, $stock->store_qty, 'Difference applied to the store');
        $this->assertSame(6.0, $stock->online_qty);

        $movement = StockMovement::sole();
        $this->assertSame(MovementType::Adjustment, $movement->type);
        $this->assertTrue($movement->user->is($this->manager));
        $this->assertStringStartsWith('Merma de tomates blandos (contado 18,5 kg, diferencia -1,5 kg)', $movement->reason);
    }

    public function test_an_adjustment_by_a_user_without_permission_is_rejected(): void
    {
        $this->actingAs(User::factory()->withRole(Role::CASHIER)->create())
            ->post('/almacen/recuentos', ['product_id' => $this->tomatoes->id, 'counted' => 5, 'reason' => 'x'])
            ->assertForbidden();

        $this->assertSame(20.0, $this->tomatoes->stock->refresh()->on_hand);
    }

    public function test_surplus_found_in_the_count_goes_to_the_store(): void
    {
        $this->recount('21');

        $this->assertSame(15.0, $this->tomatoes->stock->refresh()->store_qty);
    }

    public function test_a_very_low_count_takes_the_rest_from_free_online_stock_but_never_reservations(): void
    {
        $this->recount('3');  // store goes to 0 and online 6 -> 3 (still >= 1 reserved)

        $stock = $this->tomatoes->stock->refresh();
        $this->assertSame(0.0, $stock->store_qty);
        $this->assertSame(3.0, $stock->online_qty);
        $this->assertSame(1.0, $stock->reserved_qty);

        $this->recount('0,5')->assertSessionHasErrors('counted'); // below the 1 kg reserved
        $this->assertSame(3.0, $this->tomatoes->stock->refresh()->on_hand);
    }

    public function test_reason_is_mandatory_and_matching_counts_change_nothing(): void
    {
        $this->recount('19', '')->assertSessionHasErrors('reason');

        $this->recount('20')->assertSessionHas('status', '«Tomate»: el recuento coincide con el sistema, no hay nada que ajustar.');
        $this->assertSame(0, StockMovement::count());
    }

    public function test_unit_products_are_counted_in_whole_units(): void
    {
        $rice = Product::factory()->create();

        $this->actingAs($this->manager)->post('/almacen/recuentos', ['product_id' => $rice->id, 'counted' => '2,5', 'reason' => 'Recuento semanal'])
            ->assertSessionHasErrors('counted');
    }
}
