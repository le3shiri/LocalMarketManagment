<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSaleToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_withdraw_and_restore_a_product(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        $this->actingAs($admin)->patch("/admin/products/{$product->slug}/toggle")->assertSessionHas('status');
        $this->assertFalse($product->refresh()->is_active);

        $this->actingAs($admin)->patch("/admin/products/{$product->slug}/toggle");
        $this->assertTrue($product->refresh()->is_active);
    }

    public function test_the_product_list_shows_the_sale_switch(): void
    {
        Product::factory()->inactive()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/products')
            ->assertSee('Retirado');
    }

    public function test_cashiers_can_not_change_the_sale_status(): void
    {
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->withRole(Role::CASHIER)->create())
            ->patch("/admin/products/{$product->slug}/toggle")
            ->assertForbidden();

        $this->assertTrue($product->refresh()->is_active);
    }
}
