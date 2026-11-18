<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reports\SalesReport;
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
