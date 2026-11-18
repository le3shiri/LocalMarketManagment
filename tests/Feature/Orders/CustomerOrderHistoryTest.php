<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create();
    }

    public function test_customer_sees_their_orders_newest_first(): void
    {
        $old = Order::factory()->for($this->customer)->status(OrderStatus::Delivered)->create();
        $new = Order::factory()->for($this->customer)->create();

        $this->actingAs($this->customer)->get('/pedidos')
            ->assertOk()
            ->assertSeeInOrder([$new->code, $old->code])
            ->assertSee('Entregado');
    }

    public function test_phone_and_whatsapp_orders_linked_to_the_account_are_included(): void
    {
        Order::factory()->for($this->customer)->create(['channel' => 'whatsapp']);

        $this->actingAs($this->customer)->get('/pedidos')->assertSee('WhatsApp');
    }

    public function test_other_customers_orders_are_not_listed(): void
    {
        $foreign = Order::factory()->create();

        $this->actingAs($this->customer)->get('/pedidos')->assertDontSee($foreign->code)->assertSee('Todavía no has hecho ningún pedido');
    }

    public function test_history_is_paginated(): void
    {
        $orders = Order::factory()->count(12)->for($this->customer)->create();

        $this->actingAs($this->customer)->get('/pedidos')->assertSee($orders[11]->code)->assertDontSee($orders[1]->code);
        $this->actingAs($this->customer)->get('/pedidos?page=2')->assertSee($orders[0]->code);
    }

    public function test_navigation_links_to_my_orders(): void
    {
        $this->actingAs($this->customer)->get('/catalogo')->assertSee('Mis pedidos');
    }
}
