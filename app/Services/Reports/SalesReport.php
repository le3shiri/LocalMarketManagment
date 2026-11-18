<?php

namespace App\Services\Reports;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Sale;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use InvalidArgumentException;

/**
 * Sales for a period grouped by day, week or month:
 * till tickets + orders paid on delivery − refunds = net, plus VAT collected at the till.
 */
class SalesReport
{
    public const GROUPS = ['day' => 'Día', 'week' => 'Semana', 'month' => 'Mes'];

    /**
     * @return array{rows: array<string, array{label: string, till: float, tickets: int, orders: float, refunds: float, net: float}>, totals: array<string, float|int>, vat: array<int, array{base: float, vat: float, total: float}>}
     */
    public function build(CarbonImmutable $from, CarbonImmutable $to, string $group): array
    {
        if (! isset(self::GROUPS[$group])) {
            throw new InvalidArgumentException("Agrupación no válida: {$group}");
        }

        $from = $from->startOfDay();
        $to = $to->endOfDay();
        $rows = $this->emptyBuckets($from, $to, $group);

        foreach (Sale::whereBetween('created_at', [$from, $to])->get(['total', 'vat_breakdown', 'created_at']) as $sale) {
            $key = $this->bucket($sale->created_at, $group);
            $rows[$key]['till'] += (float) $sale->total;
            $rows[$key]['tickets']++;
        }

        $orderPayments = Payment::where('payable_type', (new Order)->getMorphClass())->whereBetween('created_at', [$from, $to])->get(['amount', 'created_at']);
        foreach ($orderPayments as $payment) {
            $rows[$this->bucket($payment->created_at, $group)]['orders'] += (float) $payment->amount;
        }

        foreach (Refund::whereBetween('created_at', [$from, $to])->get(['amount', 'created_at']) as $refund) {
            $rows[$this->bucket($refund->created_at, $group)]['refunds'] += (float) $refund->amount;
        }

        foreach ($rows as &$row) {
            $row = array_map(fn ($v) => is_float($v) ? round($v, 2) : $v, $row);
            $row['net'] = round($row['till'] + $row['orders'] - $row['refunds'], 2);
        }
        unset($row);

        $totals = [
            'till' => round(array_sum(array_column($rows, 'till')), 2),
            'tickets' => array_sum(array_column($rows, 'tickets')),
            'orders' => round(array_sum(array_column($rows, 'orders')), 2),
            'refunds' => round(array_sum(array_column($rows, 'refunds')), 2),
            'net' => round(array_sum(array_column($rows, 'net')), 2),
        ];

        return ['rows' => $rows, 'totals' => $totals, 'vat' => $this->vat($from, $to)];
    }

    /** VAT collected at the till, per rate (from each ticket's stored breakdown). */
    private function vat(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $vat = [];

        foreach (Sale::whereBetween('created_at', [$from, $to])->pluck('vat_breakdown') as $breakdown) {
            foreach ($breakdown ?? [] as $rate => $amounts) {
                foreach (['base', 'vat', 'total'] as $field) {
                    $vat[(int) $rate][$field] = round(($vat[(int) $rate][$field] ?? 0) + $amounts[$field], 2);
                }
            }
        }

        ksort($vat);

        return $vat;
    }

    private function emptyBuckets(CarbonImmutable $from, CarbonImmutable $to, string $group): array
    {
        $rows = [];
        $step = ['day' => '1 day', 'week' => '1 week', 'month' => '1 month'][$group];
        $start = match ($group) {
            'week' => $from->startOfWeek(),
            'month' => $from->startOfMonth(),
            default => $from,
        };

        foreach (CarbonPeriod::create($start, $step, $to) as $date) {
            $key = $this->bucket($date, $group);
            $rows[$key] = ['label' => $this->label($date, $group), 'till' => 0.0, 'tickets' => 0, 'orders' => 0.0, 'refunds' => 0.0, 'net' => 0.0];
        }

        return $rows;
    }

    private function bucket($date, string $group): string
    {
        $date = CarbonImmutable::parse($date);

        return match ($group) {
            'week' => $date->startOfWeek()->toDateString(),
            'month' => $date->format('Y-m'),
            default => $date->toDateString(),
        };
    }

    private function label($date, string $group): string
    {
        $date = CarbonImmutable::parse($date)->locale('es');

        return match ($group) {
            'week' => 'Semana del '.$date->startOfWeek()->format('d/m').' al '.$date->endOfWeek()->format('d/m'),
            'month' => ucfirst($date->isoFormat('MMMM YYYY')),
            default => ucfirst($date->isoFormat('ddd D/MM')),
        };
    }
}
