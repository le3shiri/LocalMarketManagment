<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<string> */
    private function labels(User $user, string $url = '/catalogo'): array
    {
        $this->actingAs($user)->get($url);

        return array_column(Navigation::for($user), 'label');
    }

    public function test_admin_gets_grouped_dropdowns_instead_of_sixteen_links(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertSame(['Panel de control', 'Tienda', 'Ventas', 'Pedidos', 'Almacén', 'Catálogo', 'Administración'], $this->labels($admin));

        $this->actingAs($admin)->get('/catalogo')
            ->assertSeeHtml('data-nav-group="Administración"')
            ->assertSeeHtml('aria-haspopup="true"')
            ->assertSee('Copias de seguridad');
    }

    public function test_staff_keep_a_short_flat_menu(): void
    {
        $this->assertSame(['Caja', 'Pedidos', 'Almacén'], $this->labels(User::factory()->withRole(Role::CASHIER)->create()));
        $this->assertSame(['Almacén'], $this->labels(User::factory()->withRole(Role::WAREHOUSE)->create()));
        $this->assertSame(['Tienda'], $this->labels(User::factory()->create()));
    }

    public function test_the_group_of_the_current_page_is_highlighted(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/admin/users');

        $menu = collect(Navigation::for($admin))->keyBy('label');
        $this->assertTrue($menu['Administración']['active']);
        $this->assertFalse($menu['Ventas']['active']);
    }

    public function test_badges_bubble_up_to_the_group(): void
    {
        $product = Product::factory()->create();
        $product->stock->update(['on_hand' => 1, 'min_alert' => 5]);

        $this->actingAs(User::factory()->withRole(Role::WAREHOUSE)->create())->get('/almacen/inventario')
            ->assertSeeHtml('data-badge>1<');
    }

    public function test_guests_only_see_catalog_and_login(): void
    {
        $this->get('/catalogo')->assertSee('Iniciar sesión')->assertSee('Crear cuenta')->assertDontSeeHtml('data-nav-group');
    }
}
