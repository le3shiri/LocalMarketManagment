<?php

namespace Tests\Feature\Orders;

use App\Enums\Fulfillment;
use App\Enums\OrderStatus;
use App\Livewire\OrderTracker;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $cashier;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create();
        $this->cashier = User::factory()->withRole(Role::CASHIER)->create();
        $this->order = Order::factory()->for($this->customer)->create(['slot_date' => '2026-11-24', 'slot' => '17:00-18:00']);
        app(OrderWorkflow::class)->recordCreation($this->order, $this->customer);
    }

    private function tracker()
    {
        return Livewire::actingAs($this->customer)->test(OrderTracker::class, ['order' => $this->order]);
    }

    public function test_order_page_shows_the_status_tracker(): void
    {
        $this->actingAs($this->customer)->get("/pedidos/{$this->order->code}")
            ->assertOk()
            ->assertSeeLivewire(OrderTracker::class);
    }

    public function test_tracker_shows_reached_steps_with_their_time(): void
    {
        $this->travelTo(now()->setTime(9, 15));
        app(OrderWorkflow::class)->advance($this->order, OrderStatus::Preparing, $this->cashier);

        $this->tracker()
            ->assertSeeInOrder(['Pendiente', 'En preparación', '09:15', 'Listo', '—', 'Entregado', '—'])
            ->assertSeeHtml('aria-current="step"');
    }

    public function test_ready_for_pickup_is_highlighted(): void
    {
        $this->order->forceFill(['status' => OrderStatus::Ready])->save();

        $this->tracker()->assertSee('¡Tu pedido está listo para recoger!')->assertSee('entre las 17:00 y las 18:00');
    }

    public function test_ready_delivery_orders_mention_the_delivery(): void
    {
        $this->order->forceFill(['status' => OrderStatus::Ready, 'fulfillment' => Fulfillment::Delivery])->save();

        $this->tracker()->assertSee('saldrá en reparto');
    }

    public function test_cancellation_reason_is_shown(): void
    {
        app(OrderWorkflow::class)->cancel($this->order, $this->cashier, 'Producto agotado y la clienta prefiere anular');

        $this->tracker()->assertSee('Pedido cancelado')->assertSee('Producto agotado y la clienta prefiere anular');
    }

    public function test_open_orders_poll_for_updates_but_finished_ones_do_not(): void
    {
        $this->tracker()->assertSeeHtml('wire:poll.30s');

        $this->order->forceFill(['status' => OrderStatus::Delivered])->save();
        $this->tracker()->assertDontSeeHtml('wire:poll.30s');
    }

    public function test_tracker_can_not_be_mounted_for_another_customers_order(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(OrderTracker::class, ['order' => $this->order])
            ->assertNotFound();
    }
}
