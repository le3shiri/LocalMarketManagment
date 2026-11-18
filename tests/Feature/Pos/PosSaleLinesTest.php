<?php

namespace Tests\Feature\Pos;

use App\Livewire\Pos\Terminal;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Support\SaleTotals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosSaleLinesTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private Product $bread;

    private Product $tomatoes;

    private Product $oil;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cashier = User::factory()->withRole(Role::CASHIER)->create();
        $this->bread = Product::factory()->create(['name' => 'Barra de pan', 'price' => 0.95, 'vat_rate' => 4]);
        $this->tomatoes = Product::factory()->byWeight()->create(['name' => 'Tomate', 'price' => 2.40, 'vat_rate' => 4]);
        $this->oil = Product::factory()->create(['name' => 'Aceite', 'price' => 8.95, 'vat_rate' => 10]);
    }

    private function terminal()
    {
        return Livewire::actingAs($this->cashier)->test(Terminal::class);
    }

    public function test_lines_and_total_update_as_products_are_added(): void
    {
        $this->terminal()
            ->call('add', $this->bread->id)
            ->call('add', $this->bread->id)
            ->call('add', $this->oil->id)
            ->assertSee('1,90')
            ->assertSeeHtml('data-total>10,85');
    }

    public function test_quantity_can_be_typed_and_weights_come_from_the_scale(): void
    {
        $component = $this->terminal()
            ->call('add', $this->tomatoes->id)
            ->call('setQuantity', $this->tomatoes->id, '1,235');

        $component->assertSet('lines', [$this->tomatoes->id => 1.235]);
        $this->assertSame(2.96, $component->instance()->totals->total); // 2,40 × 1,235 = 2,964
    }

    public function test_units_must_be_whole_numbers(): void
    {
        $this->terminal()
            ->call('add', $this->bread->id)
            ->call('setQuantity', $this->bread->id, '1.5')
            ->assertSee('se vende por unidades')
            ->assertSet('lines', [$this->bread->id => 1.0]);
    }

    public function test_zero_quantity_or_the_cross_removes_a_line_and_the_sale_can_be_voided(): void
    {
        $this->terminal()
            ->call('add', $this->bread->id)
            ->call('add', $this->oil->id)
            ->call('setQuantity', $this->bread->id, 0)
            ->assertSet('lines', [$this->oil->id => 1.0])
            ->call('removeLine', $this->oil->id)
            ->assertSet('lines', [])
            ->call('add', $this->oil->id)
            ->call('clear')
            ->assertSet('lines', []);
    }

    public function test_vat_is_broken_down_per_rate_from_vat_inclusive_prices(): void
    {
        $totals = SaleTotals::for([
            ['product' => $this->bread, 'quantity' => 2.0],  // 1,90 at 4 %
            ['product' => $this->oil, 'quantity' => 1.0],    // 8,95 at 10 %
        ]);

        $this->assertSame(10.85, $totals->total);
        $this->assertSame(['base' => 1.83, 'vat' => 0.07, 'total' => 1.9], $totals->vat[4]);
        $this->assertSame(['base' => 8.14, 'vat' => 0.81, 'total' => 8.95], $totals->vat[10]);
    }

    public function test_terminal_shows_the_vat_table(): void
    {
        $this->terminal()->call('add', $this->oil->id)->assertSee('Cuota')->assertSee('8,14');
    }
}
