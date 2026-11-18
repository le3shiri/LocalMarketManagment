<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditChain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SensitiveOperationsLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_sensitive_operations_are_flagged(): void
    {
        $this->actingAs($this->admin);
        $product = Product::factory()->create(['price' => 1.00, 'description' => 'a']);

        $product->update(['description' => 'b']);  // harmless edit
        $product->update(['price' => 1.20]);        // price change
        $cashier = Role::firstWhere('name', Role::CASHIER);
        $this->put("/admin/roles/{$cashier->id}", ['permissions' => [Permission::firstWhere('name', 'stock.entry')->id]]);
        $this->post('/logout');
        $this->post('/login', ['email' => $this->admin->email, 'password' => 'wrong']);

        $productEdits = AuditLog::where('action', 'updated')->where('subject_id', $product->id)
            ->where('subject_type', $product->getMorphClass())->orderBy('id')->pluck('is_sensitive')->all();
        $this->assertSame([false, true], $productEdits, 'Description edit is not sensitive, price change is');
        $this->assertTrue(AuditLog::where('action', 'permissions')->sole()->is_sensitive);
        $this->assertTrue(AuditLog::where('action', 'login_failed')->sole()->is_sensitive);
    }

    public function test_user_role_and_deactivation_changes_are_sensitive(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)->patch("/admin/users/{$user->id}/toggle");

        $this->assertTrue(AuditLog::where('action', 'updated')->where('subject_id', $user->id)->latest('id')->first()->is_sensitive);
    }

    public function test_entries_are_chained_and_the_chain_verifies(): void
    {
        AuditLog::record('login', 'Uno');
        AuditLog::record('stock', 'Dos', changes: ['tienda' => 14.0]);
        AuditLog::record('refund', 'Tres');

        // The admin created in setUp() already has its own "Alta" entry at the head of the chain.
        $logs = AuditLog::orderBy('id')->get();
        $this->assertCount(4, $logs);
        $this->assertNull($logs[0]->previous_hash);
        foreach ($logs->slice(1)->values() as $i => $log) {
            $this->assertSame($logs[$i]->hash, $log->previous_hash);
        }

        $this->assertSame(['valid' => true, 'checked' => 4, 'broken_at' => null], app(AuditChain::class)->verify());
        $this->artisan('audit:verify')->expectsOutputToContain('Historial íntegro: 4 entradas')->assertSuccessful();
    }

    public function test_editing_an_old_entry_is_detected(): void
    {
        AuditLog::record('refund', 'Devolución de 5 €');
        $tampered = AuditLog::record('refund', 'Devolución de 50 €');
        AuditLog::record('login', 'Otra');

        DB::table('audit_logs')->where('id', $tampered->id)->update(['description' => 'Devolución de 5 €']);

        $this->assertSame($tampered->id, app(AuditChain::class)->verify()['broken_at']);
        $this->artisan('audit:verify')->expectsOutputToContain("la cadena se rompe en la entrada #{$tampered->id}")->assertFailed();
    }

    public function test_deleting_an_entry_is_detected(): void
    {
        AuditLog::record('login', 'Uno');
        $deleted = AuditLog::record('refund', 'Dos');
        $next = AuditLog::record('login', 'Tres');

        DB::table('audit_logs')->where('id', $deleted->id)->delete();

        $this->assertSame($next->id, app(AuditChain::class)->verify()['broken_at']);
    }

    public function test_history_screen_filters_sensitive_entries_and_shows_integrity(): void
    {
        AuditLog::record('login', 'Inicio normal');
        AuditLog::record('refund', 'Devolución sospechosa');

        $this->actingAs($this->admin)->get('/admin/historial?sensibles=1')
            ->assertOk()
            ->assertSee('Devolución sospechosa')
            ->assertDontSee('Inicio normal')
            ->assertSeeHtml('data-integrity="ok"');

        DB::table('audit_logs')->orderBy('id')->limit(1)->update(['description' => 'cambiado']);
        $this->actingAs($this->admin)->get('/admin/historial')->assertSeeHtml('data-integrity="broken"');
    }
}
