<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create(['name' => 'Ana Beltrán']);
    }

    public function test_successful_and_failed_logins_are_recorded(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertDatabaseHas('audit_logs', ['action' => 'login_failed', 'user_id' => null]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'login', 'user_id' => $user->id, 'ip_address' => '127.0.0.1']);
    }

    public function test_product_changes_store_before_and_after_values(): void
    {
        $product = Product::factory()->create(['name' => 'Leche entera', 'price' => 1.05]);

        $this->actingAs($this->admin);
        $product->update(['price' => 1.15]);

        $log = AuditLog::where('action', 'updated')->latest('id')->first();
        $this->assertTrue($log->user->is($this->admin));
        $this->assertSame('Modificación de producto «Leche entera»', $log->description);
        $this->assertSame(['antes' => '1.05', 'después' => '1.15'], $log->changes['price']);
    }

    public function test_passwords_are_never_written_to_the_log(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)->put("/admin/users/{$user->id}", [
            'name' => $user->name, 'email' => $user->email, 'role_id' => $user->role_id, 'is_active' => '1',
            'password' => 'new-secret-123', 'password_confirmation' => 'new-secret-123',
        ]);

        $log = AuditLog::where('action', 'updated')->where('subject_id', $user->id)->latest('id')->first();
        $this->assertSame(['antes' => '•••', 'después' => '•••'], $log->changes['password']);
        $this->assertStringNotContainsString('new-secret-123', json_encode($log->changes));
    }

    public function test_permission_changes_and_stock_entries_are_recorded(): void
    {
        $cashier = Role::firstWhere('name', Role::CASHIER);
        $entry = Permission::firstWhere('name', 'stock.entry');
        $this->actingAs($this->admin)->put("/admin/roles/{$cashier->id}", ['permissions' => [$entry->id]]);

        $permissionsLog = AuditLog::where('action', 'permissions')->sole();
        $this->assertSame(['stock.entry'], $permissionsLog->changes['añadidos']);
        $this->assertEqualsCanonicalizing(['stock.view', 'orders.manage', 'pos.use'], $permissionsLog->changes['retirados']);

        $product = Product::factory()->create(['name' => 'Arroz bomba']);
        app(StockService::class)->registerEntry($product, 30, 10, $this->admin, 'ALB-7');
        $this->assertDatabaseHas('audit_logs', ['action' => 'stock', 'description' => 'Entrada de 40 ud de «Arroz bomba»']);
    }

    public function test_admin_can_filter_the_history(): void
    {
        $this->actingAs($this->admin);
        Product::factory()->create(['name' => 'Producto auditado']);
        AuditLog::record('login', 'Inicio de sesión de otro@example.com');

        $this->get('/admin/historial')->assertOk()->assertSee('Alta de producto «Producto auditado»');

        $this->get('/admin/historial?action=login')
            ->assertSee('Inicio de sesión de otro@example.com')
            ->assertDontSee('Producto auditado');

        $this->get("/admin/historial?user={$this->admin->id}&action=created")
            ->assertSee('Producto auditado');
    }

    public function test_only_users_with_audit_permission_see_the_history(): void
    {
        $this->actingAs(User::factory()->withRole(Role::WAREHOUSE)->create())->get('/admin/historial')->assertForbidden();
    }
}
