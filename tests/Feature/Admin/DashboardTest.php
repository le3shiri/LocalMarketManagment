<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\Reports\DashboardMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-20 12:00')); // Friday
        $this->admin = User::factory()->admin()->create();
    }

    private function paid(float $amount, string $at): void
    {
        $this->travelTo(Carbon::parse($at));
        $sale = Sale::create(['user_id' => $this->admin->id, 'total' => $amount, 'vat_breakdown' => []]);
        $sale->payment()->create(['method' => PaymentMethod::Cash, 'amount' => $amount]);
        $this->travelTo(Carbon::parse('2026-11-20 12:00'));
    }

    public function test_sales_today_are_compared_with_the_same_weekday_last_week(): void
    {
        $this->paid(150, '2026-11-20 09:00');
        $this->paid(50, '2026-11-20 10:00');
        $this->paid(160, '2026-11-13 11:00'); // previous Friday
        $this->paid(999, '2026-11-19 11:00'); // yesterday, ignored

        $this->actingAs($this->admin)->get('/admin')
            ->assertOk()
            ->assertSeeHtml('data-sales-today>200,00')
            ->assertSee('25 %')
            ->assertSeeHtml('data-sales-change');
    }

    public function test_refunds_reduce_sales_and_order_payments_count(): void
    {
        $this->paid(100, '2026-11-20 09:00');
        $order = Order::factory()->create();
        $order->payment()->create(['method' => PaymentMethod::Card, 'amount' => 30]);
        $sale = Sale::first();
        $sale->refunds()->create(['user_id' => $this->admin->id, 'reason' => 'x', 'amount' => 10])
            ->payment()->create(['method' => PaymentMethod::Cash, 'amount' => -10]);

        $this->assertSame(120.0, app(DashboardMetrics::class)->salesToday()['today']);
    }

    public function test_orders_today_by_channel_status_and_pending_to_prepare(): void
    {
        Order::factory()->create(['channel' => 'web']);
        Order::factory()->create(['channel' => 'whatsapp', 'status' => OrderStatus::Ready]);
        Order::factory()->create(['channel' => 'phone', 'slot_date' => '2026-11-25']); // pending, not due yet

        $this->actingAs($this->admin)->get('/admin')
            ->assertSeeHtml('data-orders-today>3<')
            ->assertSee('Web 1 · Teléfono 1 · WhatsApp 1')
            ->assertSeeHtml('data-pending>1<');
    }

    public function test_daily_chart_covers_fourteen_days_including_empty_ones(): void
    {
        $this->paid(40, '2026-11-15 10:00');

        $daily = app(DashboardMetrics::class)->dailySales(14);

        $this->assertCount(14, $daily);
        $this->assertSame('2026-11-07', $daily->keys()->first());
        $this->assertSame(40.0, $daily['2026-11-15']);
        $this->assertSame(0.0, $daily['2026-11-20']);
    }

    public function test_stock_alerts_are_counted(): void
    {
        $chicken = Product::factory()->create(['is_perishable' => true]);
        $chicken->stock->update(['on_hand' => 1, 'min_alert' => 3]);
        $chicken->batches()->create(['lot_code' => 'SOON', 'expires_at' => '2026-11-21', 'quantity_received' => 1, 'quantity_remaining' => 1]);
        $chicken->batches()->create(['lot_code' => 'OLD', 'expires_at' => '2026-11-18', 'quantity_received' => 1, 'quantity_remaining' => 1]);

        $this->actingAs($this->admin)->get('/admin')
            ->assertSeeHtml('data-alert="low">1<')
            ->assertSeeHtml('data-alert="soon">1<')
            ->assertSeeHtml('data-alert="expired">1<');
    }

    public function test_admin_lands_on_the_dashboard_after_login_and_others_do_not(): void
    {
        $this->post('/login', ['email' => $this->admin->email, 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
        $this->post('/logout');

        $cashier = User::factory()->withRole(Role::CASHIER)->create();
        $this->post('/login', ['email' => $cashier->email, 'password' => 'password'])->assertRedirect(route('home'));
        $this->actingAs($cashier)->get('/admin')->assertForbidden();
    }
}
