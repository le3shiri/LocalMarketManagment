<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\WriteOffType;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Models\WriteOff;
use App\Support\CsvExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-20 12:00'));
        $this->admin = User::factory()->admin()->create(['name' => 'Ana']);
    }

    private function csv(string $url): string
    {
        $response = $this->actingAs($this->admin)->get($url);
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content, 'UTF-8 BOM for Excel');

        return $content;
    }

    public function test_cells_use_spanish_formats(): void
    {
        $this->assertSame('1,25', CsvExport::cell(1.25));
        $this->assertSame('3', CsvExport::cell(3.0));
        $this->assertSame('43,40', CsvExport::money(43.4));
        $this->assertSame('Sí', CsvExport::cell(true));
        $this->assertSame('Entregado', CsvExport::cell(OrderStatus::Delivered));
        $this->assertSame('20/11/2026 12:00', CsvExport::cell(now()));
    }

    public function test_tickets_export_respects_the_method_filter(): void
    {
        foreach ([[10.0, PaymentMethod::Cash], [22.5, PaymentMethod::Card]] as [$total, $method]) {
            $sale = Sale::create(['user_id' => $this->admin->id, 'total' => $total, 'vat_breakdown' => []]);
            $sale->payment()->create(['method' => $method, 'amount' => $total]);
        }

        $csv = $this->csv('/admin/ventas/exportar?method=card');

        $this->assertStringContainsString('Ticket;Fecha;Cajero/a', $csv);
        $this->assertStringContainsString(Sale::orderBy('id')->skip(1)->first()->code.';"20/11/2026 12:00";Ana;0;Tarjeta;22,50', $csv);
        $this->assertStringNotContainsString(Sale::orderBy('id')->first()->code, $csv);
    }

    public function test_orders_export_respects_the_channel_filter(): void
    {
        Order::factory()->create(['channel' => 'whatsapp', 'contact_name' => 'Marta', 'total' => 9.40]);
        Order::factory()->create(['channel' => 'web', 'contact_name' => 'Pau']);

        $csv = $this->csv('/admin/pedidos/exportar?channel=whatsapp');

        $this->assertStringContainsString(Order::firstWhere('contact_name', 'Marta')->code.';"20/11/2026 12:00";WhatsApp;Pendiente;"Recogida en tienda"', $csv);
        $this->assertStringContainsString('Marta', $csv);
        $this->assertStringNotContainsString('Pau', $csv);
    }

    public function test_write_offs_and_movements_and_top_products_export(): void
    {
        $product = Product::factory()->create(['name' => 'Yogur']);
        WriteOff::create(['product_id' => $product->id, 'user_id' => $this->admin->id, 'type' => WriteOffType::Damaged,
            'channel' => 'store', 'quantity' => 2, 'value' => 3.2, 'notes' => 'Caja rota']);

        $this->assertStringContainsString('Yogur;"Dañado / roto";Tienda;;2;3,20;"Caja rota";Ana', $this->csv('/almacen/mermas/exportar'));
        $this->assertStringContainsString('Fecha;Producto;SKU;Tipo', $this->csv('/almacen/movimientos/exportar'));
        $this->assertStringContainsString('Puesto;Producto;Categoría', $this->csv('/admin/informes/mas-vendidos/exportar'));
    }

    public function test_every_export_is_written_to_the_audit_log(): void
    {
        $this->csv('/admin/pedidos/exportar');
        $this->csv('/admin/informes/exportar');

        $this->assertSame(2, AuditLog::where('action', 'export')->count());
        $this->assertDatabaseHas('audit_logs', ['description' => 'Exportación: pedidos (supervisión)', 'user_id' => $this->admin->id]);
    }

    public function test_screens_offer_the_export_button_and_permissions_apply(): void
    {
        $this->actingAs($this->admin)->get('/admin/ventas')->assertSeeHtml('data-export');
        $this->actingAs($this->admin)->get('/admin/pedidos?channel=web')->assertSee(route('admin.orders.export', ['channel' => 'web']), false);

        $cashier = User::factory()->withRole(Role::CASHIER)->create();
        $this->actingAs($cashier)->get('/admin/ventas/exportar')->assertForbidden();
        $this->actingAs($cashier)->get('/admin/pedidos/exportar')->assertForbidden();
    }
}
