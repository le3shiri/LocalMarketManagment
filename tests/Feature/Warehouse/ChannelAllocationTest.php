<?php

namespace Tests\Feature\Warehouse;

use App\Enums\MovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelAllocationTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $admin;

    private Product $rice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->withRole(Role::WAREHOUSE)->create();
        $this->admin = User::factory()->admin()->create();
        $this->rice = Product::factory()->create(['name' => 'Arroz bomba']);
        $this->rice->stock->update(['on_hand' => 40, 'store_qty' => 30, 'online_qty' => 10, 'reserved_qty' => 4]);
    }

    private function transfer(string $to, $quantity, ?User $user = null)
    {
        return $this->actingAs($user ?? $this->manager)->post('/almacen/canales/transferencias', [
            'product_id' => $this->rice->id, 'to' => $to, 'quantity' => $quantity, 'reason' => 'Online agotado a media mañana',
        ]);
    }

    public function test_store_to_online_transfer_keeps_real_units_and_is_logged(): void
    {
        $this->transfer('online', 5)->assertSessionHas('status');

        $stock = $this->rice->stock->refresh();
        $this->assertSame([25.0, 15.0, 40.0], [$stock->store_qty, $stock->online_qty, $stock->on_hand]);

        $movement = StockMovement::sole();
        $this->assertSame(MovementType::Transfer, $movement->type);
        $this->assertSame([-5.0, 5.0], [$movement->store_delta, $movement->online_delta]);
        $this->assertTrue($movement->user->is($this->manager));
    }

    public function test_a_transfer_bigger_than_the_available_stock_is_rejected(): void
    {
        // Mandatory test from the proposal.
        $this->transfer('online', 31)->assertSessionHasErrors('quantity');
        $this->transfer('store', 7)->assertSessionHasErrors(['quantity' => 'No se puede transferir 7 ud: solo hay 6 ud online sin reservar.']);

        $this->assertSame(0, StockMovement::count());
    }

    public function test_cashiers_can_not_transfer(): void
    {
        $this->transfer('online', 1, User::factory()->withRole(Role::CASHIER)->create())->assertForbidden();
    }

    public function test_admin_sets_the_online_shares(): void
    {
        $this->actingAs($this->admin)->put('/almacen/canales/reparto', ['online_share' => 30, 'online_share_fresh' => 15])->assertSessionHas('status');

        $this->assertSame('30', Setting::get('online_share'));
        $this->assertSame('15', Setting::get('online_share_fresh'));

        $this->actingAs($this->manager)->put('/almacen/canales/reparto', ['online_share' => 90, 'online_share_fresh' => 90])->assertForbidden();
    }

    public function test_rebalance_applies_general_and_fresh_shares_without_touching_reservations(): void
    {
        $tomatoes = Product::factory()->byWeight()->create(['name' => 'Tomate']); // perishable
        $tomatoes->stock->update(['on_hand' => 20, 'store_qty' => 20, 'online_qty' => 0]);
        Setting::set('online_share', '25');
        Setting::set('online_share_fresh', '20');

        $this->actingAs($this->admin)->post('/almacen/canales/reparto/aplicar')->assertSessionHas('status', 'Reparto aplicado: 1 productos ajustados.');

        $this->assertSame(10.0, $this->rice->stock->refresh()->online_qty, 'Already at 25 % of 40');
        $this->assertSame(4.0, $tomatoes->stock->refresh()->online_qty, '20 % of 20 kg for fresh produce');

        Setting::set('online_share', '0');
        $this->actingAs($this->admin)->post('/almacen/canales/reparto/aplicar');
        $this->assertSame(4.0, $this->rice->stock->refresh()->online_qty, 'Never below the 4 reserved');
    }

    public function test_rebalance_can_be_limited_to_a_category(): void
    {
        $fruit = Category::factory()->create();
        $apple = Product::factory()->for($fruit)->create();
        $apple->stock->update(['on_hand' => 40, 'store_qty' => 40]);
        Setting::set('online_share', '50');

        $this->actingAs($this->admin)->post('/almacen/canales/reparto/aplicar', ['category_id' => $fruit->id]);

        $this->assertSame(20.0, $apple->stock->refresh()->online_qty);
        $this->assertSame(10.0, $this->rice->stock->refresh()->online_qty, 'Other categories untouched');
    }
}
