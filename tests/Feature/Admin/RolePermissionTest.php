<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_four_roles_from_the_proposal_exist(): void
    {
        $this->assertEqualsCanonicalizing(
            [Role::ADMIN, Role::CASHIER, Role::WAREHOUSE, Role::CUSTOMER],
            Role::pluck('name')->all(),
        );
    }

    public function test_admin_has_every_permission_and_customer_only_shopping(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        foreach (Permission::pluck('name') as $permission) {
            $this->assertTrue($admin->can($permission), "admin should have {$permission}");
        }

        $this->assertTrue($customer->can('cart.use'));
        $this->assertFalse($customer->can('users.manage'));
        $this->assertFalse($customer->can('stock.entry'));
    }

    public function test_new_registrations_get_the_customer_role(): void
    {
        $this->post('/register', [
            'name' => 'Pau',
            'email' => 'pau@example.com',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
        ]);

        $this->assertTrue(User::firstWhere('email', 'pau@example.com')->hasRole(Role::CUSTOMER));
    }

    public function test_admin_can_view_the_roles_screen(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/roles')
            ->assertOk()
            ->assertSee('Encargado de almacén');
    }

    public function test_non_admins_can_not_manage_roles(): void
    {
        $this->actingAs(User::factory()->withRole(Role::WAREHOUSE)->create())
            ->get('/admin/roles')
            ->assertForbidden();
    }

    public function test_admin_can_change_the_permissions_of_a_role(): void
    {
        $cashier = Role::firstWhere('name', Role::CASHIER);
        $entry = Permission::firstWhere('name', 'stock.entry');

        $this->actingAs(User::factory()->admin()->create())
            ->put("/admin/roles/{$cashier->id}", ['permissions' => [$entry->id]])
            ->assertRedirect();

        $this->assertSame(['stock.entry'], $cashier->permissions()->pluck('name')->all());
    }

    public function test_admin_role_can_not_lose_role_management(): void
    {
        $admin = User::factory()->admin()->create();
        $role = $admin->role;

        $this->actingAs($admin)->put("/admin/roles/{$role->id}", ['permissions' => []]);

        $this->assertTrue($role->permissions()->where('name', 'roles.manage')->exists());
    }
}
