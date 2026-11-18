<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\Reports\TopProducts;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TopProductsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $oil;

    private Product $bread;

    private Product $tomato;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $pantry = Category::factory()->create(['name' => 'Despensa']);
        $this->oil = Product::factory()->for($pantry)->create(['name' => 'Aceite', 'price' => 9]);
        $this->bread = Product::factory()->create(['name' => 'Pan', 'price' => 1]);
        $this->tomato = Product::factory()->byWeight()->create(['name' => 'Tomate', 'price' => 2]);

        // Previous period (1-10 Nov): oil 18 €
        $this->sale('2026-11-05 10:00', [[$this->oil, 2]]);
        // Current period (11-20 Nov)
        $this->sale('2026-11-12 10:00', [[$this->oil, 3], [$this->bread, 10]]);   // oil 27 €, bread 10 €
        $refunded = $this->sale('2026-11-13 10:00', [[$this->bread, 5]]);          // bread +5, 1 returned
        $refunded->refunds()->create(['user_id' => $this->admin->id, 'reason' => 'x', 'amount' => 1])
            ->items()->create(['sale_item_id' => $refunded->items()->first()->id, 'quantity' => 1, 'amount' => 1]);

        $order = Order::factory()->create(['status' => OrderStatus::Delivered, 'slot_date' => '2026-11-15']);
        $order->items()->create(['product_id' => $this->tomato->id, 'product_name' => 'Tomate', 'sku' => 'T', 'unit_type' => 'weight',
            'unit_price' => 2, 'quantity' => 1, 'delivered_quantity' => 1.2, 'line_total' => 2.40]);
        $pending = Order::factory()->create(['status' => OrderStatus::Pending, 'slot_date' => '2026-11-15']);
        $pending->items()->create(['product_id' => $this->oil->id, 'product_name' => 'Aceite', 'sku' => 'A', 'unit_type' => 'unit',
            'unit_price' => 9, 'quantity' => 50, 'line_total' => 450]);  // not delivered: must not count

        $this->travelTo(Carbon::parse('2026-11-20 12:00'));
    }

    private function sale(string $at, array $lines): Sale
    {
        $this->travelTo(Carbon::parse($at));
        $sale = Sale::create(['user_id' => $this->admin->id, 'total' => 0, 'vat_breakdown' => []]);
        foreach ($lines as [$product, $qty]) {
            $sale->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku,
                'unit_type' => $product->unit_type, 'unit_price' => $product->price, 'vat_rate' => 10, 'quantity' => $qty,
                'line_total' => $product->price * $qty]);
        }

        return $sale;
    }

    private function top(string $channel = 'all', ?int $category = null, string $sort = 'revenue')
    {
        return app(TopProducts::class)->build(CarbonImmutable::parse('2026-11-11'), CarbonImmutable::parse('2026-11-20'), $channel, $category, $sort);
    }

    public function test_ranking_combines_till_net_of_returns_and_delivered_orders(): void
    {
        $rows = $this->top()->keyBy('name');

        $this->assertSame(['Aceite', 'Pan', 'Tomate'], $this->top()->pluck('name')->all());
        $this->assertSame([3.0, 27.0], [$rows['Aceite']['quantity'], $rows['Aceite']['revenue']]);
        $this->assertSame([14.0, 14.0], [$rows['Pan']['quantity'], $rows['Pan']['revenue']], '15 sold - 1 returned');
        $this->assertSame([1.2, 2.4], [$rows['Tomate']['quantity'], $rows['Tomate']['revenue']], 'Real delivered weight');
    }

    public function test_trend_against_the_previous_period(): void
    {
        $rows = $this->top()->keyBy('name');

        $this->assertSame(50.0, $rows['Aceite']['trend'], '18 € -> 27 €');
        $this->assertNull($rows['Pan']['trend'], 'New this period');
    }

    public function test_filters_by_channel_category_and_sort(): void
    {
        $this->assertSame(['Tomate'], $this->top('online')->pluck('name')->all());
        $this->assertSame(['Aceite', 'Pan'], $this->top('till')->pluck('name')->all());
        $this->assertSame(['Aceite'], $this->top(category: $this->oil->category_id)->pluck('name')->all());
        $this->assertSame('Pan', $this->top(sort: 'quantity')->first()['name']);
    }

    public function test_report_page_and_dashboard_top_five(): void
    {
        $this->actingAs($this->admin)->get('/admin/informes/mas-vendidos?from=2026-11-11&to=2026-11-20')
            ->assertOk()
            ->assertSeeInOrder(['Aceite', '27,00', 'Pan', 'Tomate'])
            ->assertSeeHtml('data-trend="50"')
            ->assertSee('Nuevo');

        $this->actingAs($this->admin)->get('/admin')->assertSee('Más vendidos (30 días)')->assertSeeInOrder(['Aceite', 'Pan']);
    }

    public function test_only_report_viewers_can_see_it(): void
    {
        $this->actingAs(User::factory()->withRole(Role::CASHIER)->create())->get('/admin/informes/mas-vendidos')->assertForbidden();
    }
}
