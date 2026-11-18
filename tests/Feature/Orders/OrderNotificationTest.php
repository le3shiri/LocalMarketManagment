<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use App\Services\OrderService;
use App\Services\OrderWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-23 08:00'));
        $this->customer = User::factory()->create(['name' => 'Marta', 'phone' => '600123456']);
        $this->cashier = User::factory()->withRole(Role::CASHIER)->create();
    }

    private function placeOrder(): Order
    {
        $product = Product::factory()->create();
        $product->stock->update(['on_hand' => 10, 'online_qty' => 10]);
        $this->customer->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);

        return app(OrderService::class)->placeFromCart($this->customer, [
            'fulfillment' => 'pickup', 'contact_name' => 'Marta', 'contact_phone' => '600123456',
            'slot_date' => '2026-11-23', 'slot' => '17:00-18:00',
        ]);
    }

    public function test_customer_is_notified_when_the_order_is_confirmed(): void
    {
        Notification::fake();

        $order = $this->placeOrder();

        Notification::assertSentTo($this->customer, OrderStatusNotification::class,
            fn ($n, $channels) => $n->status === OrderStatus::Pending
                && $n->title() === "Pedido {$order->code} confirmado"
                && $channels === ['database', 'mail']);
    }

    public function test_customer_is_notified_when_ready_but_not_for_internal_steps(): void
    {
        $order = $this->placeOrder();
        Notification::fake();

        app(OrderWorkflow::class)->advance($order, OrderStatus::Preparing, $this->cashier);
        Notification::assertNothingSent();

        app(OrderWorkflow::class)->advance($order, OrderStatus::Ready, $this->cashier);
        Notification::assertSentTo($this->customer, OrderStatusNotification::class,
            fn ($n) => $n->status === OrderStatus::Ready && str_contains($n->title(), 'listo para recoger'));
    }

    public function test_cancellation_notice_includes_the_reason(): void
    {
        $order = $this->placeOrder();
        Notification::fake();

        app(OrderWorkflow::class)->cancel($order, $this->cashier, 'Sin stock de pan');

        Notification::assertSentTo($this->customer, OrderStatusNotification::class,
            fn ($n) => $n->status === OrderStatus::Cancelled && str_contains($n->body(), 'Motivo: Sin stock de pan'));
    }

    public function test_guests_without_account_are_not_notified(): void
    {
        Notification::fake();
        $order = Order::factory()->create(['user_id' => null]);

        app(OrderWorkflow::class)->cancel($order, $this->cashier, 'Duplicado');

        Notification::assertNothingSent();
    }

    public function test_notifications_are_stored_and_shown_with_an_unread_counter(): void
    {
        $order = $this->placeOrder();

        $this->actingAs($this->customer)->get('/catalogo')->assertSeeHtml('data-unread>1<');
        $this->actingAs($this->customer)->get('/avisos')->assertSee("Pedido {$order->code} confirmado");

        $id = $this->customer->notifications()->first()->id;
        $this->actingAs($this->customer)->get("/avisos/{$id}")->assertRedirect(route('orders.show', $order));
        $this->assertSame(0, $this->customer->unreadNotifications()->count());
    }

    public function test_all_notifications_can_be_marked_as_read(): void
    {
        $order = $this->placeOrder();
        app(OrderWorkflow::class)->cancel($order, $this->cashier, 'Prueba');

        $this->actingAs($this->customer)->post('/avisos/leidos');

        $this->assertSame(0, $this->customer->unreadNotifications()->count());
    }

    public function test_a_customer_can_not_open_someone_elses_notification(): void
    {
        $this->placeOrder();
        $id = $this->customer->notifications()->first()->id;

        $this->actingAs(User::factory()->create())->get("/avisos/{$id}")->assertNotFound();
    }

    public function test_confirmation_email_links_to_the_order(): void
    {
        $order = $this->placeOrder();

        $mail = (new OrderStatusNotification($order, OrderStatus::Pending))->toMail($this->customer);

        $this->assertSame("Pedido {$order->code} confirmado", $mail->subject);
        $this->assertSame(route('orders.show', $order), $mail->actionUrl);
    }
}
