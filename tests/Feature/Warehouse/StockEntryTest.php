<?php

namespace Tests\Feature\Warehouse;

use App\Enums\MovementType;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->withRole(Role::WAREHOUSE)->create();
    }

    public function test_entry_increases_stock_and_is_split_between_channels(): void
    {
        $product = Product::factory()->byWeight()->create();

        $this->actingAs($this->manager)->post('/almacen/entradas', [
            'product_id' => $product->id,
            'store_qty' => '14',
            'online_qty' => '5,5',
            'lot_code' => 'L-TOM-01', // perishable: batch data required since SUP-51
            'expires_at' => now()->addDays(5)->toDateString(),
            'reference' => 'ALB-2026-118',
        ])->assertSessionHasNoErrors()->assertRedirect(route('warehouse.entries.create'));

        $stock = $product->stock->refresh();
        $this->assertSame(19.5, $stock->on_hand);
        $this->assertSame(14.0, $stock->store_qty);
        $this->assertSame(5.5, $stock->online_qty);
    }

    public function test_entry_is_written_to_the_ledger_with_the_person_responsible(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->manager)->post('/almacen/entradas', [
            'product_id' => $product->id, 'store_qty' => 30, 'online_qty' => 10, 'reference' => 'ALB-1',
        ]);

        $movement = StockMovement::sole();
        $this->assertSame(MovementType::Entry, $movement->type);
        $this->assertTrue($movement->user->is($this->manager));
        $this->assertSame(40.0, $movement->on_hand_after);
        $this->assertSame('ALB-1', $movement->reference);
    }

    public function test_negative_or_zero_entries_are_rejected(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->manager)
            ->post('/almacen/entradas', ['product_id' => $product->id, 'store_qty' => -5, 'online_qty' => 0])
            ->assertSessionHasErrors('store_qty');

        $this->actingAs($this->manager)
            ->post('/almacen/entradas', ['product_id' => $product->id, 'store_qty' => 0, 'online_qty' => 0])
            ->assertSessionHasErrors('store_qty');

        $this->assertSame(0.0, $product->stock->refresh()->on_hand);
    }

    public function test_unit_products_only_accept_whole_quantities(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->manager)
            ->post('/almacen/entradas', ['product_id' => $product->id, 'store_qty' => '1.5', 'online_qty' => 0])
            ->assertSessionHasErrors('store_qty');
    }

    public function test_cashiers_can_not_register_entries(): void
    {
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->withRole(Role::CASHIER)->create())
            ->post('/almacen/entradas', ['product_id' => $product->id, 'store_qty' => 5, 'online_qty' => 0])
            ->assertForbidden();
    }

    public function test_entry_form_lists_recent_movements(): void
    {
        $product = Product::factory()->create(['name' => 'Arroz bomba 1 kg']);
        $this->actingAs($this->manager)->post('/almacen/entradas', ['product_id' => $product->id, 'store_qty' => 10, 'online_qty' => 2]);

        $this->actingAs($this->manager)->get('/almacen/entradas/nueva')
            ->assertOk()
            ->assertSee('Arroz bomba 1 kg')
            ->assertSee('Entrada de mercancía');
    }
}
