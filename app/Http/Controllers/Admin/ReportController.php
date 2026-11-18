<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Reports\SalesReport;
use App\Services\Reports\TopProducts;
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
        $n = fn (float $v) => number_format($v, 2, ',', '');

        return response()->streamDownload(function () use ($data, $n) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Periodo', 'Tickets', 'Ventas caja', 'Pedidos cobrados', 'Devoluciones', 'Neto'], ';');
            foreach ($data['rows'] as $row) {
                fputcsv($out, [$row['label'], $row['tickets'], $n($row['till']), $n($row['orders']), $n($row['refunds']), $n($row['net'])], ';');
            }
            $t = $data['totals'];
            fputcsv($out, ['TOTAL', $t['tickets'], $n($t['till']), $n($t['orders']), $n($t['refunds']), $n($t['net'])], ';');
            fputcsv($out, [], ';');
            fputcsv($out, ['IVA (caja)', 'Base', 'Cuota', 'Total'], ';');
            foreach ($data['vat'] as $rate => $vat) {
                fputcsv($out, ["{$rate} %", $n($vat['base']), $n($vat['vat']), $n($vat['total'])], ';');
            }
            fclose($out);
        }, "informe-ventas-{$from->format('Ymd')}-{$to->format('Ymd')}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
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
