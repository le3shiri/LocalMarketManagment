<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $laura;

    private User $jordi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-23 19:00'));
        $this->admin = User::factory()->admin()->create();
        $this->laura = User::factory()->withRole(Role::CASHIER)->create(['name' => 'Laura']);
        $this->jordi = User::factory()->withRole(Role::CASHIER)->create(['name' => 'Jordi']);

        $this->sale($this->laura, 10.00, PaymentMethod::Cash, '2026-11-23 10:00');
        $this->sale($this->laura, 20.00, PaymentMethod::Card, '2026-11-23 11:00');
        $this->sale($this->jordi, 6.00, PaymentMethod::Cash, '2026-11-22 12:00');
        $this->sale($this->jordi, 99.00, PaymentMethod::Cash, '2026-11-01 12:00'); // outside default 7 days

        $order = Order::factory()->create(['total' => 15]);
        $order->payment()->create(['method' => PaymentMethod::Card, 'amount' => 15, 'user_id' => $this->laura->id]);
    }

    private function sale(User $cashier, float $total, PaymentMethod $method, string $at): Sale
    {
        $this->travelTo(Carbon::parse($at));
        $sale = Sale::create(['user_id' => $cashier->id, 'total' => $total, 'vat_breakdown' => []]);
        $sale->payment()->create(['method' => $method, 'amount' => $total, 'user_id' => $cashier->id]);
        $this->travelTo(Carbon::parse('2026-11-23 19:00'));

        return $sale;
    }

    public function test_default_period_is_the_last_seven_days(): void
    {
        $this->actingAs($this->admin)->get('/admin/ventas')
            ->assertOk()
            ->assertSee('Del 17/11/2026 al 23/11/2026')
            ->assertSeeHtml('data-ticket-count>3<')
            ->assertSeeHtml('data-sales-total>36,00')
            ->assertSeeHtml('data-average>12,00');
    }

    public function test_payments_by_method_include_counter_sales_and_paid_orders(): void
    {
        $byMethod = $this->actingAs($this->admin)->get('/admin/ventas')->viewData('byMethod');

        $this->assertSame(16.0, (float) $byMethod['cash']->sum('amount'));
        $this->assertSame(35.0, (float) $byMethod['card']->sum('amount'), '20 at the till + 15 from an order');
    }

    public function test_filters_by_date_cashier_and_method(): void
    {
        $this->actingAs($this->admin)->get('/admin/ventas?from=2026-11-22&to=2026-11-22')->assertSeeHtml('data-ticket-count>1<');
        $this->actingAs($this->admin)->get("/admin/ventas?cashier={$this->jordi->id}")->assertSeeHtml('data-sales-total>6,00');
        $this->actingAs($this->admin)->get('/admin/ventas?method=card')->assertSeeHtml('data-ticket-count>1<');
    }

    public function test_sales_are_grouped_per_day_and_tickets_link_to_the_receipt(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/ventas');

        $this->assertSame(['2026-11-23', '2026-11-22'], $response->viewData('byDay')->pluck('day')->all());
        $response->assertSee(route('pos.tickets.show', 'T-000001'));
    }

    public function test_only_users_with_sales_permission_see_the_report(): void
    {
        $this->actingAs($this->laura)->get('/admin/ventas')->assertForbidden();
    }
}
