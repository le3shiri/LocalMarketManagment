<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

class BackupTest extends TestCase
{
    // Not RefreshDatabase: SQLite cannot VACUUM INTO inside the transaction it wraps tests in.
    use DatabaseMigrations;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = storage_path('framework/testing/backups-'.uniqid());
        config(['backup.path' => $this->dir, 'backup.keep' => 3]);
        $this->travelTo(Carbon::parse('2026-11-20 02:00:00'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    public function test_backup_creates_a_compressed_sqlite_copy_and_is_audited(): void
    {
        $this->artisan('backup:run')->expectsOutputToContain('Copia creada: localmarket-20261120-020000.sqlite.gz')->assertSuccessful();

        $dump = gzdecode(File::get("{$this->dir}/localmarket-20261120-020000.sqlite.gz"));
        $this->assertStringStartsWith("SQLite format 3\0", $dump);

        $log = AuditLog::where('action', 'backup')->sole();
        $this->assertTrue($log->is_sensitive);
    }

    public function test_backups_rotate_and_never_overwrite_each_other(): void
    {
        $service = app(BackupService::class);
        $names = [$service->create(), $service->create()];
        $this->assertSame(['localmarket-20261120-020000.sqlite.gz', 'localmarket-20261120-020000-2.sqlite.gz'], $names);

        foreach (range(1, 3) as $minute) {
            $this->travelTo(Carbon::parse("2026-11-20 02:0{$minute}:00"));
            $service->create();
        }

        $this->assertCount(3, $service->list());
        $this->assertSame('localmarket-20261120-020300.sqlite.gz', $service->list()->first()['name']);
    }

    public function test_restore_brings_back_the_data_and_keeps_a_safety_copy(): void
    {
        // A real file-based SQLite database, since an in-memory one can't be replaced.
        $file = "{$this->dir}/restore-test.sqlite";
        File::ensureDirectoryExists($this->dir);
        touch($file);
        config(['database.connections.restore_test' => ['driver' => 'sqlite', 'database' => $file, 'prefix' => '', 'foreign_key_constraints' => true]]);
        $db = DB::connection('restore_test');
        $db->statement('create table notes (id integer primary key, body text)');
        $db->table('notes')->insert(['body' => 'pedido correcto']);

        $service = app(BackupService::class)->onConnection('restore_test');
        $backup = $service->create();

        $db->table('notes')->update(['body' => 'dato estropeado']);
        $this->travelTo(Carbon::parse('2026-11-20 02:05:00'));
        $safety = $service->restore($backup);

        $this->assertSame('pedido correcto', DB::connection('restore_test')->table('notes')->value('body'));
        $this->assertSame('localmarket-20261120-020500.sqlite.gz', $safety);
        $this->assertTrue(AuditLog::where('description', 'like', "Restauración de la copia {$backup}%")->exists());
        DB::purge('restore_test');
    }

    public function test_restore_requires_confirmation_and_rejects_unknown_or_foreign_files(): void
    {
        app(BackupService::class)->create();

        $this->artisan('backup:restore', ['file' => 'localmarket-20261120-020000.sqlite.gz'])
            ->expectsConfirmation('Se van a REEMPLAZAR todos los datos actuales por los de localmarket-20261120-020000.sqlite.gz. ¿Continuar?', 'no')
            ->expectsOutputToContain('Restauración cancelada')
            ->assertFailed();

        $this->artisan('backup:restore', ['file' => '../../.env', '--force' => true])->expectsOutputToContain('no existe')->assertFailed();
    }

    public function test_admin_lists_creates_and_downloads_backups(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/copias')->assertSessionHas('status');
        $this->actingAs($admin)->get('/admin/copias')
            ->assertOk()
            ->assertSee('localmarket-20261120-020000.sqlite.gz')
            ->assertSee('php artisan backup:restore');

        $this->actingAs($admin)->get('/admin/copias/localmarket-20261120-020000.sqlite.gz')->assertOk()->assertDownload('localmarket-20261120-020000.sqlite.gz');
        $this->actingAs($admin)->get('/admin/copias/..%2F..%2F.env')->assertNotFound();
        $this->actingAs($admin)->get('/admin/copias/database.sqlite')->assertNotFound();
    }

    public function test_only_backup_managers_can_access_and_backups_are_scheduled_daily(): void
    {
        $this->actingAs(User::factory()->withRole(Role::CASHIER)->create())->get('/admin/copias')->assertForbidden();

        $this->assertTrue(collect(Schedule::events())->contains(fn ($e) => str_contains($e->command, 'backup:run') && $e->expression === '0 2 * * *'));
    }
}
