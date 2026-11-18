<?php

namespace Tests\Feature\Admin;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\Reports\SalesReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SalesReportByPeriodTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();

        $this->sale(10.40, '2026-11-02 10:00', [4 => ['base' => 10.0, 'vat' => 0.4, 'total' => 10.4]]);
        $this->sale(22.00, '2026-11-02 12:00', [10 => ['base' => 20.0, 'vat' => 2.0, 'total' => 22.0]]);
        $this->sale(11.00, '2026-11-10 12:00', [10 => ['base' => 10.0, 'vat' => 1.0, 'total' => 11.0]]);
        $this->orderPaid(15.00, '2026-11-03 18:00');
        $this->refund(5.00, '2026-11-10 13:00');

        $this->travelTo(Carbon::parse('2026-11-20 12:00'));
    }

    private function sale(float $total, string $at, array $vat): void
    {
        $this->travelTo(Carbon::parse($at));
        Sale::create(['user_id' => $this->admin->id, 'total' => $total, 'vat_breakdown' => $vat]);
    }

    private function orderPaid(float $amount, string $at): void
    {
        $this->travelTo(Carbon::parse($at));
        Order::factory()->create()->payment()->create(['method' => PaymentMethod::Card, 'amount' => $amount]);
    }

    private function refund(float $amount, string $at): void
    {
        $this->travelTo(Carbon::parse($at));
        Sale::first()->refunds()->create(['user_id' => $this->admin->id, 'reason' => 'x', 'amount' => $amount]);
    }

    private function report(string $group, string $from = '2026-11-01', string $to = '2026-11-15'): array
    {
        return app(SalesReport::class)->build(CarbonImmutable::parse($from), CarbonImmutable::parse($to), $group);
    }

    public function test_daily_report_has_every_day_and_net_figures(): void
    {
        $report = $this->report('day');

        $this->assertCount(15, $report['rows']);
        $this->assertSame(['till' => 32.4, 'tickets' => 2], array_intersect_key($report['rows']['2026-11-02'], ['till' => 0, 'tickets' => 0]));
        $this->assertSame(15.0, $report['rows']['2026-11-03']['orders']);
        $this->assertSame(6.0, $report['rows']['2026-11-10']['net'], '11 - 5 refunded');
        $this->assertSame(['till' => 43.4, 'tickets' => 3, 'orders' => 15.0, 'refunds' => 5.0, 'net' => 53.4], $report['totals']);
    }

    public function test_weekly_and_monthly_grouping(): void
    {
        $weekly = $this->report('week');
        $this->assertSame(['2026-10-26', '2026-11-02', '2026-11-09'], array_keys($weekly['rows']));
        $this->assertSame(47.4, $weekly['rows']['2026-11-02']['net']);

        $monthly = $this->report('month', '2026-10-01', '2026-11-30');
        $this->assertSame(['2026-10', '2026-11'], array_keys($monthly['rows']));
        $this->assertSame('Noviembre 2026', $monthly['rows']['2026-11']['label']);
    }

    public function test_vat_collected_at_the_till_is_summed_per_rate(): void
    {
        $vat = $this->report('day')['vat'];

        $this->assertSame(['base' => 10.0, 'vat' => 0.4, 'total' => 10.4], $vat[4]);
        $this->assertSame(['base' => 30.0, 'vat' => 3.0, 'total' => 33.0], $vat[10]);
    }

    public function test_report_page_and_csv_export(): void
    {
        $this->actingAs($this->admin)->get('/admin/informes?from=2026-11-01&to=2026-11-15&group=week')
            ->assertOk()
            ->assertSeeHtml('data-total="net">')
            ->assertSee('53,40')
            ->assertSee('Semana del 02/11 al 08/11')
            ->assertSee('Imprimir');

        $csv = $this->actingAs($this->admin)->get('/admin/informes/exportar?from=2026-11-01&to=2026-11-15&group=week')->streamedContent();
        $this->assertStringContainsString('Periodo;Tickets;"Ventas caja"', $csv);
        $this->assertStringContainsString('TOTAL;3;43,40;15,00;5,00;53,40', $csv);
        $this->assertStringContainsString('"10 %";30,00;3,00;33,00', $csv);
    }

    public function test_defaults_to_the_current_month_by_day_and_validates_the_range(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/informes');
        $this->assertSame('2026-11-01', $response->viewData('from')->toDateString());
        $this->assertSame('day', $response->viewData('group'));

        $this->actingAs($this->admin)->get('/admin/informes?from=2026-11-10&to=2026-11-01')->assertSessionHasErrors('to');
    }

    public function test_only_users_with_reports_permission_can_see_them(): void
    {
        $this->actingAs(User::factory()->withRole(Role::CASHIER)->create())->get('/admin/informes')->assertForbidden();
    }
}
