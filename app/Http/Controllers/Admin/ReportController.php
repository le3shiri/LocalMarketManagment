<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Reports\SalesReport;
use App\Services\Reports\TopProducts;
use App\Support\CsvExport;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, SalesReport $report): View
    {
        [$from, $to, $group] = $this->period($request);

        return view('admin.reports.index', ['from' => $from, 'to' => $to, 'group' => $group, 'groups' => SalesReport::GROUPS]
            + $report->build($from, $to, $group));
    }

    public function export(Request $request, SalesReport $report): StreamedResponse
    {
        [$from, $to, $group] = $this->period($request);
        $data = $report->build($from, $to, $group);
        $m = [CsvExport::class, 'money'];

        return CsvExport::download("informe-ventas-{$from->format('Ymd')}-{$to->format('Ymd')}",
            ['Periodo', 'Tickets', 'Ventas caja', 'Pedidos cobrados', 'Devoluciones', 'Neto'],
            function ($write) use ($data, $m) {
                foreach ($data['rows'] as $row) {
                    $write([$row['label'], $row['tickets'], $m($row['till']), $m($row['orders']), $m($row['refunds']), $m($row['net'])]);
                }
                $t = $data['totals'];
                $write(['TOTAL', $t['tickets'], $m($t['till']), $m($t['orders']), $m($t['refunds']), $m($t['net'])]);
                $write([]);
                $write(['IVA (caja)', 'Base', 'Cuota', 'Total']);
                foreach ($data['vat'] as $rate => $vat) {
                    $write(["{$rate} %", $m($vat['base']), $m($vat['vat']), $m($vat['total'])]);
                }
            },
            "informe de ventas {$from->format('d/m/Y')}–{$to->format('d/m/Y')}",
        );
    }

    public function exportTopProducts(Request $request, TopProducts $top): StreamedResponse
    {
        [$from, $to] = $this->period($request);
        $filters = $this->topFilters($request);
        $rows = $top->build($from, $to, $filters['channel'], $filters['category'], $filters['sort'], $filters['limit']);

        return CsvExport::download('mas-vendidos',
            ['Puesto', 'Producto', 'Categoría', 'Cantidad', 'Unidad', 'Importe', 'Periodo anterior', 'Tendencia %'],
            fn ($write) => $rows->each(fn ($r) => $write([
                $r['rank'], $r['name'], $r['category'], $r['quantity'], $r['unit'],
                CsvExport::money($r['revenue']), CsvExport::money($r['previous_revenue']), $r['trend'] ?? 'Nuevo',
            ])),
            "productos más vendidos {$from->format('d/m/Y')}–{$to->format('d/m/Y')}",
        );
    }

    public function topProducts(Request $request, TopProducts $top): View
    {
        [$from, $to] = $this->period($request);
        $filters = $this->topFilters($request);

        return view('admin.reports.top-products', [
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'rows' => $top->build($from, $to, $filters['channel'], $filters['category'], $filters['sort'], $filters['limit']),
            'channels' => TopProducts::CHANNELS,
            'categories' => Category::ordered()->get(['id', 'name']),
        ]);
    }

    /** @return array{channel: string, category: ?int, sort: string, limit: int} */
    private function topFilters(Request $request): array
    {
        $data = $request->validate([
            'channel' => ['nullable', Rule::in(array_keys(TopProducts::CHANNELS))],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'sort' => ['nullable', Rule::in(['revenue', 'quantity'])],
            'limit' => ['nullable', 'integer', Rule::in([10, 20, 50])],
        ]);

        return [
            'channel' => $data['channel'] ?? 'all',
            'category' => isset($data['category']) ? (int) $data['category'] : null,
            'sort' => $data['sort'] ?? 'revenue',
            'limit' => (int) ($data['limit'] ?? 20),
        ];
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string} */
    private function period(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'group' => ['nullable', Rule::in(array_keys(SalesReport::GROUPS))],
        ]);

        $from = CarbonImmutable::parse($data['from'] ?? today()->startOfMonth());
        $to = CarbonImmutable::parse($data['to'] ?? today());

        // Keep reports reasonable: at most two years per request.
        if ($from->diffInDays($to) > 731) {
            $from = $to->subYears(2);
        }

        return [$from, $to, $data['group'] ?? 'day'];
    }
}
