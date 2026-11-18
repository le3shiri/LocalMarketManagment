<?php

namespace Tests\Feature\Orders;

use App\Livewire\Staff\CreateOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class PhoneOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private Product $rice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-23 08:00'));
        $this->cashier = User::factory()->withRole(Role::CASHIER)->create();
        $this->rice = Product::factory()->create(['name' => 'Arroz bomba', 'sku' => 'DES-001', 'price' => 3.75]);
        $this->rice->stock->update(['on_hand' => 20, 'store_qty' => 10, 'online_qty' => 10]);
    }

    private function form()
    {
        return Livewire::actingAs($this->cashier)->withQueryParams(['canal' => 'phone'])->test(CreateOrder::class);
    }

    public function test_staff_can_open_the_phone_order_form(): void
    {
        $this->actingAs($this->cashier)->get('/gestion/pedidos/nuevo?canal=phone')->assertOk()->assertSee('Nuevo pedido por teléfono');
    }

    public function test_products_are_searched_and_added_to_the_order(): void
    {
        $this->form()
            ->set('search', 'arroz')
            ->assertSee('Arroz bomba')
            ->call('addProduct', $this->rice->id)
            ->call('addProduct', $this->rice->id)
            ->assertSet('lines', [$this->rice->id => 2.0])
            ->assertSee('7,50');
    }

    public function test_phone_order_for_a_guest_is_registered_with_the_phone_channel(): void
    {
        $this->form()
            ->set('contact_phone', '611 222 333')
            ->set('contact_name', 'Sr. Pere')
            ->call('addProduct', $this->rice->id)
            ->set('slot', '11:00-12:00')
            ->call('save')
            ->assertRedirect(route('staff.orders.show', 'LM-000001'));

        $order = Order::sole();
        $this->assertSame('phone', $order->channel);
        $this->assertNull($order->user_id);
        $this->assertTrue($order->creator->is($this->cashier));
        $this->assertSame(1.0, $this->rice->stock->refresh()->reserved_qty, 'Same reservation as the web');
    }

    public function test_known_customers_are_recognised_by_phone_and_linked(): void
    {
        $marta = User::factory()->create(['name' => 'Marta Puig', 'phone' => '600 123 456']);

        $this->form()
            ->set('contact_phone', '+34600123456')
            ->assertSet('contact_name', 'Marta Puig')
            ->assertSee('Cliente registrado')
            ->call('addProduct', $this->rice->id)
            ->set('slot', '11:00-12:00')
            ->call('save');

        $this->assertTrue(Order::sole()->user->is($marta));
    }

    public function test_stock_rules_are_the_same_as_on_the_web(): void
    {
        $this->form()
            ->set('contact_phone', '611222333')->set('contact_name', 'Pere')
            ->call('setQuantity', $this->rice->id, 11)
            ->set('slot', '11:00-12:00')
            ->call('save')
            ->assertSee('solo quedan 10 ud');

        $this->assertSame(0, Order::count());
    }

    public function test_an_order_needs_at_least_one_product(): void
    {
        $this->form()
            ->set('contact_phone', '611222333')->set('contact_name', 'Pere')
            ->set('slot', '11:00-12:00')
            ->call('save')
            ->assertSet('error', 'Añade al menos un producto al pedido.');
    }

    public function test_customers_can_not_register_staff_orders(): void
    {
        $this->actingAs(User::factory()->create())->get('/gestion/pedidos/nuevo?canal=phone')->assertForbidden();
    }
}
