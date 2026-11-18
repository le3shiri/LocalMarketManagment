<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cashier = User::factory()->withRole(Role::CASHIER)->create(['name' => 'Laura Caja']);
    }

    private function advance(Order $order, OrderStatus $to, ?string $note = null)
    {
        return $this->actingAs($this->cashier)->patch("/gestion/pedidos/{$order->code}/estado", ['status' => $to->value, 'note' => $note]);
    }

    public function test_staff_panel_lists_open_orders_by_slot(): void
    {
        Order::factory()->create(['slot' => '18:00-19:00', 'contact_name' => 'Segundo']);
        Order::factory()->create(['slot' => '10:00-11:00', 'contact_name' => 'Primero']);
        Order::factory()->status(OrderStatus::Delivered)->create(['contact_name' => 'Ya entregado']);

        $this->actingAs($this->cashier)->get('/gestion/pedidos')
            ->assertOk()
            ->assertSeeInOrder(['Primero', 'Segundo'])
            ->assertDontSee('Ya entregado');
    }

    public function test_an_order_goes_through_the_full_life_cycle(): void
    {
        $order = Order::factory()->create();

        $this->advance($order, OrderStatus::Preparing)->assertSessionHas('status');
        $this->advance($order, OrderStatus::Ready);
        $this->advance($order, OrderStatus::Delivered, 'Recogido por la clienta');

        $this->assertSame(OrderStatus::Delivered, $order->refresh()->status);
        $this->assertSame(
            ['preparing', 'ready', 'delivered'],
            $order->statusChanges->map(fn ($c) => $c->to_status->value)->all(),
        );
        $this->assertTrue($order->statusChanges->last()->user->is($this->cashier));
        $this->assertSame('Recogido por la clienta', $order->statusChanges->last()->note);
    }

    public function test_steps_can_not_be_skipped_or_reversed(): void
    {
        $order = Order::factory()->create();

        $this->advance($order, OrderStatus::Delivered)->assertSessionHas('error');
        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);

        $ready = Order::factory()->status(OrderStatus::Ready)->create();
        $this->advance($ready, OrderStatus::Preparing)->assertSessionHas('error');
        $this->assertSame(OrderStatus::Ready, $ready->refresh()->status);
    }

    public function test_finished_orders_can_not_change(): void
    {
        $order = Order::factory()->status(OrderStatus::Delivered)->create();

        $this->advance($order, OrderStatus::Ready)->assertSessionHas('error');
    }

    public function test_status_changes_are_audited(): void
    {
        $order = Order::factory()->create();

        $this->advance($order, OrderStatus::Preparing);

        $this->assertDatabaseHas('audit_logs', ['action' => 'order', 'description' => "Pedido {$order->code}: Pendiente → En preparación"]);
    }

    public function test_order_detail_shows_lines_and_timeline(): void
    {
        $order = Order::factory()->create();
        $this->advance($order, OrderStatus::Preparing);

        $this->actingAs($this->cashier)->get("/gestion/pedidos/{$order->code}")
            ->assertOk()
            ->assertSee('Historial del pedido')
            ->assertSee('Laura Caja')
            ->assertSee('Marcar como listo');
    }

    public function test_customers_and_warehouse_staff_can_not_manage_orders(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->create())->get('/gestion/pedidos')->assertForbidden();
        $this->actingAs(User::factory()->withRole(Role::WAREHOUSE)->create())
            ->patch("/gestion/pedidos/{$order->code}/estado", ['status' => 'preparing'])
            ->assertForbidden();
    }
}
