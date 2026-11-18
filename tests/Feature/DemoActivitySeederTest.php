<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\User;
use App\Services\AuditChain;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\DemoActivitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DemoActivitySeederTest extends TestCase
{
    use RefreshDatabase;

    private function seedDemo(int $weeks = 1): void
    {
        $this->travelTo(Carbon::parse('2026-11-20 12:00'));
        $this->seed(CatalogSeeder::class);
        User::factory()->withRole('cajero')->create(['email' => 'caja@localmarket.test']);
        User::factory()->withRole('almacen')->create(['email' => 'almacen@localmarket.test']);
        User::factory()->admin()->create(['email' => 'admin@localmarket.test']);
        User::factory()->create(['email' => 'cliente@localmarket.test']);

        $seeder = app(DemoActivitySeeder::class);
        $seeder->weeks = $weeks;
        $seeder->run();
        $this->travelTo(Carbon::parse('2026-11-20 12:00'));
    }

    public function test_generates_sales_orders_in_every_channel_and_history_for_the_demo_customer(): void
    {
        $this->seedDemo();

        $this->assertGreaterThan(40, Sale::count());
        $this->assertEqualsCanonicalizing(['web', 'phone', 'whatsapp'], Order::distinct()->pluck('channel')->all());
        $this->assertTrue(Order::where('status', 'delivered')->exists());
        $this->assertTrue(Order::whereIn('status', ['pending', 'preparing', 'ready'])->exists(), 'Open orders for the staff panel');
        $this->assertTrue(Order::where('channel', 'whatsapp')->whereNotNull('source_message')->exists());
        $this->assertTrue(User::firstWhere('email', 'cliente@localmarket.test')->orders()->exists());
    }

    public function test_generated_data_is_consistent(): void
    {
        $this->seedDemo();

        $this->assertSame(0, ProductStock::where('store_qty', '<', 0)->orWhere('online_qty', '<', 0)->orWhere('reserved_qty', '<', 0)->count());
        $this->assertEqualsWithDelta(
            (float) OrderItem::whereHas('order', fn ($q) => $q->whereIn('status', ['pending', 'preparing', 'ready']))->sum('quantity'),
            (float) ProductStock::sum('reserved_qty'),
            0.001,
            'Reserved stock equals exactly what open orders hold',
        );
        $this->assertSame(Sale::count() + Order::where('status', 'delivered')->count(), Payment::where('amount', '>', 0)->count(), 'Every ticket and delivered order is paid');
        $this->assertTrue(app(AuditChain::class)->verify()['valid']);
    }

    public function test_history_is_spread_over_the_period_and_closed_on_sundays(): void
    {
        $this->seedDemo();

        $days = Sale::pluck('created_at')->map->toDateString()->unique();
        $this->assertGreaterThanOrEqual(5, $days->count());
        $this->assertFalse($days->contains(fn ($d) => Carbon::parse($d)->isSunday()));
    }

    public function test_it_does_not_run_twice(): void
    {
        $this->seedDemo();
        $sales = Sale::count();

        app(DemoActivitySeeder::class)->run();

        $this->assertSame($sales, Sale::count());
    }
}
