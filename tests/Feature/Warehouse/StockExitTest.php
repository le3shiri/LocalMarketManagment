<?php

namespace Tests\Feature\Warehouse;

use App\Enums\MovementType;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockExitTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Product $rice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->withRole(Role::WAREHOUSE)->create();
        $this->rice = Product::factory()->create(['name' => 'Arroz bomba']);
        // 10 in store, 6 online of which 4 reserved -> 2 free online.
        $this->rice->stock->update(['on_hand' => 16, 'store_qty' => 10, 'online_qty' => 6, 'reserved_qty' => 4]);
    }

    private function exit(array $data)
    {
        return $this->actingAs($this->manager)->post('/almacen/salidas', $data + [
            'product_id' => $this->rice->id, 'channel' => 'store', 'reason' => 'supplier_return',
        ]);
    }

    public function test_exit_from_store_is_recorded_with_reason_and_responsible(): void
    {
        $this->exit(['quantity' => 3, 'reference' => 'DEV-77'])->assertSessionHasNoErrors()->assertSessionHas('status');

        $stock = $this->rice->stock->refresh();
        $this->assertSame(7.0, $stock->store_qty);
        $this->assertSame(13.0, $stock->on_hand);

        $movement = StockMovement::sole();
        $this->assertSame(MovementType::Exit, $movement->type);
        $this->assertSame('Devolución a proveedor', $movement->reason);
        $this->assertSame('DEV-77', $movement->reference);
        $this->assertTrue($movement->user->is($this->manager));
    }

    public function test_exit_from_online_only_uses_unreserved_units(): void
    {
        $this->exit(['channel' => 'online', 'quantity' => 3, 'reason' => 'donation'])
            ->assertSessionHasErrors(['quantity' => 'Solo hay 2 ud de «Arroz bomba» online sin reservar.']);

        $this->exit(['channel' => 'online', 'quantity' => 2, 'reason' => 'donation'])->assertSessionHasNoErrors();

        $stock = $this->rice->stock->refresh();
        $this->assertSame(4.0, $stock->online_qty);
        $this->assertSame(4.0, $stock->reserved_qty, 'Reservations untouched');
    }

    public function test_exits_never_leave_negative_stock(): void
    {
        $this->exit(['quantity' => 11])->assertSessionHasErrors('quantity');
        $this->assertSame(10.0, $this->rice->stock->refresh()->store_qty);
    }

    public function test_other_reason_requires_details_and_units_must_be_whole(): void
    {
        $this->exit(['quantity' => 1, 'reason' => 'other'])->assertSessionHasErrors('notes');
        $this->exit(['quantity' => '1,5'])->assertSessionHasErrors('quantity');

        $this->exit(['quantity' => 1, 'reason' => 'other', 'notes' => 'Muestra para cata'])->assertSessionHasNoErrors();
        $this->assertSame('Otro motivo: Muestra para cata', StockMovement::sole()->reason);
    }

    public function test_cashiers_can_not_register_exits(): void
    {
        $this->actingAs(User::factory()->withRole(Role::CASHIER)->create())
            ->post('/almacen/salidas', ['product_id' => $this->rice->id, 'channel' => 'store', 'quantity' => 1, 'reason' => 'donation'])
            ->assertForbidden();
    }
}
