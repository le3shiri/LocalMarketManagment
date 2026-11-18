<?php

namespace Tests\Feature\Admin;

use App\Enums\Fulfillment;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderSupervisionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();

        Order::factory()->create(['channel' => 'web', 'total' => 20, 'contact_name' => 'Cliente Web', 'slot_date' => '2026-11-20']);
        Order::factory()->create(['channel' => 'phone', 'total' => 15, 'contact_name' => 'Cliente Tel', 'slot_date' => '2026-11-21', 'fulfillment' => Fulfillment::Delivery]);
        Order::factory()->create(['channel' => 'whatsapp', 'total' => 10, 'contact_name' => 'Cliente Wa', 'slot_date' => '2026-11-21']);
        Order::factory()->status(OrderStatus::Cancelled)->create(['channel' => 'web', 'total' => 99, 'contact_name' => 'Cancelado', 'slot_date' => '2026-11-21']);
    }

    private function visit(string $query = '')
    {
        return $this->actingAs($this->admin)->get('/admin/pedidos'.$query);
    }

    public function test_admin_sees_orders_from_every_channel(): void
    {
        $this->visit()->assertOk()->assertSee(['Cliente Web', 'Cliente Tel', 'Cliente Wa', 'Cancelado']);
    }

    public function test_totals_per_channel_exclude_cancelled_orders(): void
    {
        $response = $this->visit();

        $response->assertSeeInOrder(['Total', '3', 'pedidos', '45,00']);
        $this->assertSame(20.0, (float) $response->viewData('byChannel')['web']->amount);
    }

    public function test_orders_can_be_filtered_by_channel_status_and_fulfillment(): void
    {
        $this->visit('?channel=whatsapp')->assertSee('Cliente Wa')->assertDontSee('Cliente Web');
        $this->visit('?status=cancelled')->assertSee('Cancelado')->assertDontSee('Cliente Tel');
        $this->visit('?fulfillment=delivery')->assertSee('Cliente Tel')->assertDontSee('Cliente Wa');
    }

    public function test_orders_can_be_filtered_by_date_and_searched(): void
    {
        $this->visit('?from=2026-11-21&to=2026-11-21')->assertSee('Cliente Tel')->assertDontSee('Cliente Web');
        $this->visit('?q=LM-000001')->assertSee('Cliente Web')->assertDontSee('Cliente Tel');
        $this->visit('?to=2026-11-01&from=2026-11-21')->assertSessionHasErrors('to');
    }

    public function test_orders_per_day_and_status_are_summarised(): void
    {
        $byDay = $this->visit()->viewData('byDay');

        $this->assertSame(['2026-11-21', '2026-11-20'], $byDay->keys()->all());
        $this->assertSame(3, $byDay['2026-11-21']->sum('orders'));
    }

    public function test_only_the_admin_can_supervise_all_orders(): void
    {
        $this->actingAs(User::factory()->withRole(Role::CASHIER)->create())->get('/admin/pedidos')->assertForbidden();
    }
}
