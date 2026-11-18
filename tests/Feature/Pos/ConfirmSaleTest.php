<?php

namespace Tests\Feature\Pos;

use App\Enums\MovementType;
use App\Enums\PaymentMethod;
use App\Livewire\Pos\Terminal;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConfirmSaleTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private Product $bread;

    private Product $oil;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cashier = User::factory()->withRole(Role::CASHIER)->create(['name' => 'Laura Caja']);
        $this->bread = Product::factory()->create(['name' => 'Barra de pan', 'price' => 0.95, 'vat_rate' => 4]);
        $this->oil = Product::factory()->create(['name' => 'Aceite', 'price' => 8.95, 'vat_rate' => 10]);
        foreach ([$this->bread, $this->oil] as $product) {
            $product->stock->update(['on_hand' => 10, 'store_qty' => 7, 'online_qty' => 3]);
        }
    }

    private function sale()
    {
        return Livewire::actingAs($this->cashier)->test(Terminal::class)
            ->call('add', $this->bread->id)
            ->call('add', $this->bread->id)
            ->call('add', $this->oil->id);
    }

    public function test_cash_sale_is_saved_with_ticket_number_lines_and_payment(): void
    {
        $this->sale()->set('tendered', '20')->call('confirmSale')
            ->assertRedirect(route('pos.tickets.show', ['sale' => 'T-000001', 'nueva' => 1]));

        $sale = Sale::sole();
        $this->assertSame('T-000001', $sale->code);
        $this->assertSame('10.85', $sale->total);
        $this->assertSame(2, $sale->items()->count());
        $this->assertTrue($sale->cashier->is($this->cashier));

        $payment = $sale->payment;
        $this->assertSame(PaymentMethod::Cash, $payment->method);
        $this->assertSame('20.00', $payment->tendered);
        $this->assertSame('9.15', $payment->change);
    }

    public function test_sale_takes_units_from_store_stock_only(): void
    {
        $this->sale()->set('paymentMethod', 'card')->call('confirmSale');

        $stock = $this->bread->stock->refresh();
        $this->assertSame(5.0, $stock->store_qty);
        $this->assertSame(8.0, $stock->on_hand);
        $this->assertSame(3.0, $stock->online_qty, 'Online channel untouched');
        $this->assertSame(2, StockMovement::where('type', MovementType::Sale)->count());
    }

    public function test_sale_is_rejected_when_store_stock_does_not_cover_it_and_nothing_is_saved(): void
    {
        $this->oil->stock->update(['store_qty' => 0]);

        $this->sale()->set('tendered', '20')->call('confirmSale')
            ->assertNoRedirect()
            ->assertSee('solo hay 0 ud de «Aceite» en tienda');

        $this->assertSame(0, Sale::count());
        $this->assertSame(0, Payment::count());
        $this->assertSame(7.0, $this->bread->stock->refresh()->store_qty, 'Rolled back');
    }

    public function test_terminal_is_emptied_after_a_sale(): void
    {
        $component = $this->sale()->set('tendered', '20')->call('confirmSale');

        $component->assertSet('lines', [])->assertSet('tendered', '');
    }

    public function test_ticket_shows_lines_vat_payment_and_change(): void
    {
        $this->sale()->set('tendered', '20')->call('confirmSale');

        $this->actingAs($this->cashier)->get('/caja/tickets/T-000001?nueva=1')
            ->assertOk()
            ->assertSee('FACTURA SIMPLIFICADA')
            ->assertSee('Le atendió: Laura Caja')
            ->assertSeeInOrder(['Barra de pan', '1,90', 'Aceite', '8,95'])
            ->assertSeeHtml('data-ticket-total>10,85')
            ->assertSee('Cambio')
            ->assertSee('9,15')
            ->assertSee('Imprimir ticket');
    }

    public function test_sales_are_written_to_the_operations_history(): void
    {
        $this->sale()->set('paymentMethod', 'card')->set('authCode', '123ABC')->call('confirmSale');

        $this->assertDatabaseHas('audit_logs', ['action' => 'sale', 'description' => 'Venta T-000001 (Tarjeta, 10,85 €)']);
        $this->assertSame('123ABC', Payment::sole()->reference);
    }
}
