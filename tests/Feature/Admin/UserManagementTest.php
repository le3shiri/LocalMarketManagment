<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_list_and_search_users(): void
    {
        User::factory()->create(['name' => 'Marta Puig']);
        User::factory()->create(['name' => 'Pau Serra']);

        $this->actingAs($this->admin)->get('/admin/users?q=Marta')
            ->assertOk()
            ->assertSee('Marta Puig')
            ->assertDontSee('Pau Serra');
    }

    public function test_admin_can_create_a_staff_account(): void
    {
        $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Laura Caja',
            'email' => 'laura@localmarket.test',
            'role_id' => Role::idFor(Role::CASHIER),
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertTrue(User::firstWhere('email', 'laura@localmarket.test')->hasRole(Role::CASHIER));
    }

    public function test_admin_can_edit_a_user_without_changing_the_password(): void
    {
        $user = User::factory()->create();
        $hash = $user->password;

        $this->actingAs($this->admin)->put("/admin/users/{$user->id}", [
            'name' => 'Nombre nuevo',
            'email' => $user->email,
            'role_id' => Role::idFor(Role::WAREHOUSE),
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Nombre nuevo', $user->name);
        $this->assertTrue($user->hasRole(Role::WAREHOUSE));
        $this->assertSame($hash, $user->password);
    }

    public function test_deactivated_users_can_not_log_in(): void
    {
        $user = User::factory()->create();
        $this->actingAs($this->admin)->patch("/admin/users/{$user->id}/toggle");
        $this->assertFalse($user->refresh()->is_active);

        auth()->logout();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_deactivated_user_with_an_open_session_is_signed_out(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)->get('/cuenta')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_can_not_deactivate_or_demote_themselves(): void
    {
        $this->actingAs($this->admin)->patch("/admin/users/{$this->admin->id}/toggle")->assertSessionHas('error');

        $this->actingAs($this->admin)->put("/admin/users/{$this->admin->id}", [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role_id' => Role::idFor(Role::CUSTOMER),
            'is_active' => '0',
        ])->assertSessionHasErrors(['role_id', 'is_active']);

        $this->assertTrue($this->admin->refresh()->is_active);
    }

    public function test_customers_can_not_access_user_management(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/users')->assertForbidden();
    }
}
