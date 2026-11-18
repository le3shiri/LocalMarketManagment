<?php

namespace Tests\Feature\Warehouse;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ExpiryAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

class ExpiryAlertTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $admin;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-20 07:30'));
        config(['shop.expiry_warning_days' => 3]);
        $this->manager = User::factory()->withRole(Role::WAREHOUSE)->create();
        $this->admin = User::factory()->admin()->create();
        $this->cashier = User::factory()->withRole(Role::CASHIER)->create();

        $yogurt = Product::factory()->create(['name' => 'Yogur', 'is_perishable' => true]);
        $yogurt->batches()->create(['lot_code' => 'SOON', 'expires_at' => '2026-11-21', 'quantity_received' => 6, 'quantity_remaining' => 6]);
        $yogurt->batches()->create(['lot_code' => 'LATER', 'expires_at' => '2026-12-15', 'quantity_received' => 6, 'quantity_remaining' => 6]);
    }

    public function test_warehouse_staff_get_one_alert_with_the_batches_expiring_soon(): void
    {
        Notification::fake();

        $this->artisan('stock:expiry-alerts')->expectsOutputToContain('1 lote(s) caducan pronto')->assertSuccessful();

        Notification::assertSentTo([$this->manager, $this->admin], ExpiryAlertNotification::class,
            fn ($n, $channels) => $channels === ['database', 'mail']
                && $n->batches->pluck('lot_code')->all() === ['SOON']
                && $n->lines() === ['Yogur · lote SOON · 6 ud · caduca mañana']);
        Notification::assertNotSentTo($this->cashier, ExpiryAlertNotification::class);
    }

    public function test_the_same_batch_is_not_announced_twice_the_same_day(): void
    {
        Notification::fake();

        $this->artisan('stock:expiry-alerts');
        $this->artisan('stock:expiry-alerts')->expectsOutputToContain('Ningún lote nuevo');

        Notification::assertSentToTimes($this->manager, ExpiryAlertNotification::class, 1);

        $this->travelTo(Carbon::parse('2026-11-21 07:30'));
        $this->artisan('stock:expiry-alerts');
        Notification::assertSentToTimes($this->manager, ExpiryAlertNotification::class, 2);
    }

    public function test_alert_appears_in_the_web_notices_and_links_to_the_expiry_screen(): void
    {
        $this->artisan('stock:expiry-alerts');

        $this->actingAs($this->manager)->get('/avisos')->assertSee('1 lote caduca pronto')->assertSee('lote SOON');

        $id = $this->manager->notifications()->first()->id;
        $this->actingAs($this->manager)->get("/avisos/{$id}")->assertRedirect(route('warehouse.expiry.index'));
    }

    public function test_alerts_are_scheduled_every_morning(): void
    {
        $this->assertTrue(collect(Schedule::events())->contains(fn ($e) => str_contains($e->command, 'stock:expiry-alerts') && $e->expression === '30 7 * * *'));
    }

    public function test_alert_mail_lists_the_batches(): void
    {
        $this->artisan('stock:expiry-alerts');
        $notification = new ExpiryAlertNotification(\App\Models\StockBatch::with('product')->where('lot_code', 'SOON')->get());

        $mail = $notification->toMail($this->manager);

        $this->assertSame('LocalMarket · 1 lote caduca pronto', $mail->subject);
        $this->assertContains('· Yogur · lote SOON · 6 ud · caduca mañana', $mail->introLines);
    }
}
