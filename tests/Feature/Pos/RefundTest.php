<?php

namespace Tests\Feature\Pos;

use App\Enums\MovementType;
use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SaleService;
use App\Support\SaleTotals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $oil;

    private Product $yogurt;

    private Sale $sale;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-10 10:00'));
        $this->admin = User::factory()->admin()->create();
        $cashier = User::factory()->withRole(Role::CASHIER)->create();

        $this->oil = Product::factory()->create(['name' => 'Aceite', 'price' => 8.95, 'is_perishable' => false]);
        $this->yogurt = Product::factory()->create(['name' => 'Yogur', 'price' => 1.60, 'is_perishable' => true]);
        foreach ([$this->oil, $this->yogurt] as $product) {
            $product->stock->update(['on_hand' => 10, 'store_qty' => 10]);
        }

        $this->sale = app(SaleService::class)->complete(
            SaleTotals::for([['product' => $this->oil, 'quantity' => 2.0], ['product' => $this->yogurt, 'quantity' => 3.0]]),
            ['method' => PaymentMethod::Card, 'tendered' => null, 'change' => null, 'reference' => null],
            $cashier,
        );
        $this->travelTo(Carbon::parse('2026-11-12 18:00'));
    }

    private function line(Product $product): int
    {
        return $this->sale->items()->where('product_id', $product->id)->value('id');
    }

    private function refund(array $quantities, string $reason = 'Producto defectuoso')
    {
        return $this->actingAs($this->admin)->post("/caja/tickets/{$this->sale->code}/devolucion", [
            'quantities' => $quantities, 'reason' => $reason,
        ]);
    }

    public function test_partial_refund_creates_a_return_ticket_and_a_negative_payment_with_the_same_method(): void
    {
        $this->refund([$this->line($this->oil) => 1])->assertRedirect(route('pos.refunds.show', 'D-000001'));

        $refund = Refund::sole();
        $this->assertSame('8.95', $refund->amount);
        $this->assertSame(PaymentMethod::Card, $refund->payment->method);
        $this->assertSame('-8.95', $refund->payment->amount);

        $this->actingAs($this->admin)->get('/caja/devoluciones/D-000001')
            ->assertSee('TICKET DE DEVOLUCIÓN')
            ->assertSeeHtml('data-refund-total>-8,95');
    }

    public function test_non_perishables_go_back_to_the_shelf_and_perishables_do_not(): void
    {
        $this->refund([$this->line($this->oil) => 2, $this->line($this->yogurt) => 3]);

        $this->assertSame(10.0, $this->oil->stock->refresh()->store_qty, 'Oil back on the shelf');
        $this->assertSame(7.0, $this->yogurt->stock->refresh()->store_qty, 'Yogurt is not resold');
        $this->assertSame(1, StockMovement::where('type', MovementType::Return)->count());
        $this->assertSame('22.70', Refund::sole()->amount, '2 × 8,95 + 3 × 1,60');
    }

    public function test_can_not_return_more_than_sold_minus_already_returned(): void
    {
        $this->refund([$this->line($this->oil) => 1]);

        $this->refund([$this->line($this->oil) => 2])->assertSessionHas('error', 'De «Aceite» solo se pueden devolver 1 ud.');
        $this->assertSame(1, Refund::count());
    }

    public function test_reason_is_mandatory_and_quantities_must_be_positive(): void
    {
        $this->refund([$this->line($this->oil) => 1], '')->assertSessionHasErrors('reason');
        $this->refund([$this->line($this->oil) => 0])->assertSessionHas('error', 'Indica al menos una cantidad a devolver.');
        $this->assertSame(0, Refund::count());
    }

    public function test_tickets_older_than_fifteen_days_can_not_be_refunded(): void
    {
        $this->travelTo(Carbon::parse('2026-11-26 10:00'));

        $this->refund([$this->line($this->oil) => 1])->assertSessionHas('error');
        $this->assertSame(0, Refund::count());
    }

    public function test_lines_from_another_ticket_are_rejected(): void
    {
        $this->refund([999 => 1])->assertSessionHas('error', 'La línea no pertenece a este ticket.');
    }

    public function test_refunds_reduce_the_net_card_payments_in_the_report(): void
    {
        $this->refund([$this->line($this->oil) => 1]);

        $this->assertSame(13.75, (float) Payment::where('method', 'card')->sum('amount'));

        $this->actingAs($this->admin)->get('/admin/ventas?from=2026-11-10&to=2026-11-12')
            ->assertSeeHtml('data-refunds-total>-8,95')
            ->assertSee('Devoluciones');
    }

    public function test_ticket_lists_its_refunds_and_cashiers_can_not_refund_by_default(): void
    {
        $this->refund([$this->line($this->oil) => 1]);
        $this->actingAs($this->admin)->get("/caja/tickets/{$this->sale->code}")->assertSee('D-000001');

        $this->actingAs(User::factory()->withRole(Role::CASHIER)->create())
            ->post("/caja/tickets/{$this->sale->code}/devolucion", ['quantities' => [$this->line($this->oil) => 1], 'reason' => 'x'])
            ->assertForbidden();
    }
}
