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

class WhatsAppOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private Product $eggs;

    private const MESSAGE = 'Hola! Quiero 6 huevos para mañana por la tarde';

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-23 08:00'));
        $this->cashier = User::factory()->withRole(Role::CASHIER)->create();
        $this->eggs = Product::factory()->create(['name' => 'Huevos camperos (media docena)', 'price' => 1.85]);
        $this->eggs->stock->update(['on_hand' => 30, 'store_qty' => 20, 'online_qty' => 10]);
    }

    private function filledForm(string $phone = '600123456')
    {
        return Livewire::actingAs($this->cashier)
            ->withQueryParams(['canal' => 'whatsapp'])
            ->test(CreateOrder::class)
            ->set('contact_phone', $phone)
            ->set('contact_name', 'Marta')
            ->set('source_message', self::MESSAGE)
            ->set('source_reference', 'Chat Marta 09:42')
            ->call('addProduct', $this->eggs->id)
            ->set('slot_date', '2026-11-24')
            ->set('slot', '17:00-18:00');
    }

    public function test_whatsapp_order_keeps_channel_and_original_message(): void
    {
        $this->filledForm()->call('save')->assertRedirect(route('staff.orders.show', 'LM-000001'));

        $order = Order::sole();
        $this->assertSame('whatsapp', $order->channel);
        $this->assertSame(self::MESSAGE, $order->source_message);
        $this->assertSame('Chat Marta 09:42', $order->source_reference);

        $this->actingAs($this->cashier)->get('/gestion/pedidos/LM-000001')
            ->assertSee('WhatsApp')
            ->assertSee(self::MESSAGE);
    }

    public function test_the_original_message_is_required_for_whatsapp(): void
    {
        $this->filledForm()->set('source_message', '')->call('save')->assertHasErrors('source_message');
    }

    public function test_phone_orders_ignore_whatsapp_fields(): void
    {
        Livewire::actingAs($this->cashier)->withQueryParams(['canal' => 'phone'])->test(CreateOrder::class)
            ->set('contact_phone', '611222333')->set('contact_name', 'Pere')
            ->set('source_message', 'no debería guardarse')
            ->call('addProduct', $this->eggs->id)
            ->set('slot', '17:00-18:00')
            ->call('save');

        $this->assertNull(Order::sole()->source_message);
    }

    public function test_duplicate_from_same_phone_and_slot_must_be_confirmed(): void
    {
        $this->filledForm()->call('save');

        $second = $this->filledForm('+34 600 123 456')
            ->call('save')
            ->assertSet('duplicates', ['LM-000001'])
            ->assertSee('Posible pedido duplicado')
            ->assertNoRedirect();
        $this->assertSame(1, Order::count());

        $second->call('saveAnyway')->assertRedirect(route('staff.orders.show', 'LM-000002'));
        $this->assertSame(2, Order::count());
    }

    public function test_a_different_slot_is_not_a_duplicate(): void
    {
        $this->filledForm()->call('save');

        $this->filledForm()->set('slot', '18:00-19:00')->call('save')->assertSet('duplicates', []);

        $this->assertSame(2, Order::count());
    }

    public function test_unknown_channels_are_rejected(): void
    {
        $this->actingAs($this->cashier)->get('/gestion/pedidos/nuevo?canal=fax')->assertNotFound();
    }
}
