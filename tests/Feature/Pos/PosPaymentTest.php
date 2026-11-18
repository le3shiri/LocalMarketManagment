<?php

namespace Tests\Feature\Pos;

use App\Enums\PaymentMethod;
use App\Livewire\Pos\Terminal;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private Product $oil;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cashier = User::factory()->withRole(Role::CASHIER)->create();
        $this->oil = Product::factory()->create(['name' => 'Aceite', 'price' => 8.95]);
    }

    private function saleOfOil()
    {
        return Livewire::actingAs($this->cashier)->test(Terminal::class)->call('add', $this->oil->id);
    }

    public function test_cash_change_is_calculated_while_typing(): void
    {
        $component = $this->saleOfOil()->set('tendered', '20');

        $component->assertSee('Cambio')->assertSeeHtml('data-change>11,05');
        $this->assertSame(11.05, $component->instance()->change);
    }

    public function test_quick_note_buttons_add_up_and_exact_fills_the_total(): void
    {
        $this->saleOfOil()
            ->call('addTendered', 5)
            ->call('addTendered', 5)
            ->assertSet('tendered', '10')
            ->call('exactAmount')
            ->assertSet('tendered', '8.95');
    }

    public function test_cash_below_the_total_is_rejected(): void
    {
        $component = $this->saleOfOil()->set('tendered', '5');

        $component->assertSee('Falta');
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $component->instance()->validatePayment();
    }

    public function test_valid_cash_payment_returns_tendered_and_change(): void
    {
        $payment = $this->saleOfOil()->set('tendered', '10,00')->instance()->validatePayment();

        $this->assertSame(PaymentMethod::Cash, $payment['method']);
        $this->assertSame(10.0, $payment['tendered']);
        $this->assertSame(1.05, $payment['change']);
    }

    public function test_card_payment_only_keeps_an_optional_authorisation_code(): void
    {
        $payment = $this->saleOfOil()
            ->set('paymentMethod', 'card')
            ->set('authCode', 'a1b2c3')
            ->assertSee('datáfono')
            ->instance()->validatePayment();

        $this->assertSame(PaymentMethod::Card, $payment['method']);
        $this->assertSame('A1B2C3', $payment['reference']);
        $this->assertNull($payment['change']);
    }

    public function test_authorisation_code_must_be_a_short_reference(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->saleOfOil()->set('paymentMethod', 'card')->set('authCode', '4111 1111 1111 1111')->instance()->validatePayment();
    }

    public function test_an_empty_sale_can_not_be_charged(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        Livewire::actingAs($this->cashier)->test(Terminal::class)->set('tendered', '5')->instance()->validatePayment();
    }
}
