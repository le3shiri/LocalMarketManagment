<?php

namespace App\Services\Reports;

use App\Enums\OrderStatus;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Best-selling products over a period: counter sales (net of returns) plus delivered
 * orders (real delivered quantity), with the trend against the previous period.
 */
class TopProducts
{
    public const CHANNELS = ['all' => 'Todos', 'till' => 'Caja', 'online' => 'Pedidos (web, teléfono, WhatsApp)'];

    /**
     * @return Collection<int, array{product_id: ?int, name: string, category: ?string, unit: string, quantity: float, revenue: float, previous_revenue: float, trend: ?float, rank: int}>
     */
    public function build(CarbonImmutable $from, CarbonImmutable $to, string $channel = 'all', ?int $categoryId = null, string $sortBy = 'revenue', int $limit = 20): Collection
    {
        $from = $from->startOfDay();
        $to = $to->endOfDay();
        $days = (int) $from->diffInDays($to) + 1;

        $current = $this->totals($from, $to, $channel);
        $previous = $this->totals($from->subDays($days), $from->subSecond(), $channel);

        // Keys are product ids, or "deleted:<name>" for products removed from the catalogue.
        $ids = $current->keys()->filter(fn ($key) => is_numeric($key))->values();
        $products = Product::with('category')->whereIn('id', $ids)->get()->keyBy('id');

        return $current
            ->map(function (array $row, $key) use ($products, $previous) {
                $product = $products->get($key);
                $before = $previous->get($key)['revenue'] ?? 0.0;

                return [
                    'product_id' => $product?->id,
                    'name' => $product?->name ?? $row['name'],
                    'category' => $product?->category?->name,
                    'category_id' => $product?->category_id,
                    'unit' => $product?->unit_type->suffix() ?? $row['unit'],
                    'quantity' => round($row['quantity'], 3),
                    'revenue' => round($row['revenue'], 2),
                    'previous_revenue' => round($before, 2),
                    'trend' => $before > 0 ? round(100 * ($row['revenue'] - $before) / $before, 1) : null,
                ];
            })
            ->when($categoryId, fn ($rows) => $rows->where('category_id', $categoryId))
            ->filter(fn ($row) => $row['quantity'] > 0)
            ->sortByDesc($sortBy === 'quantity' ? 'quantity' : 'revenue')
            ->take($limit)
            ->values()
            ->map(fn ($row, $i) => $row + ['rank' => $i + 1]);
    }

    /** @return Collection<int|string, array{name: string, unit: string, quantity: float, revenue: float}> keyed by product id */
    private function totals(CarbonImmutable $from, CarbonImmutable $to, string $channel): Collection
    {
        $rows = collect();

        if ($channel !== 'online') {
            // Counter sales minus whatever was returned of each line.
            $sold = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->leftJoin(DB::raw('(select sale_item_id, sum(quantity) as qty, sum(amount) as amount from refund_items group by sale_item_id) as r'), 'r.sale_item_id', '=', 'sale_items.id')
                ->whereBetween('sales.created_at', [$from, $to])
                ->groupBy('sale_items.product_id', 'sale_items.product_name', 'sale_items.unit_type')
                ->selectRaw('sale_items.product_id, sale_items.product_name as name, sale_items.unit_type as unit,
                    sum(sale_items.quantity - coalesce(r.qty, 0)) as quantity, sum(sale_items.line_total - coalesce(r.amount, 0)) as revenue')
                ->get();
            $rows = $rows->concat($sold);
        }

        if ($channel !== 'till') {
            $delivered = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.status', OrderStatus::Delivered->value)
                ->whereBetween('orders.slot_date', [$from->toDateString(), $to->toDateString().' 23:59:59'])
                ->groupBy('order_items.product_id', 'order_items.product_name', 'order_items.unit_type')
                ->selectRaw('order_items.product_id, order_items.product_name as name, order_items.unit_type as unit,
                    sum(coalesce(order_items.delivered_quantity, order_items.quantity)) as quantity, sum(order_items.line_total) as revenue')
                ->get();
            $rows = $rows->concat($delivered);
        }

        return $rows->groupBy(fn ($row) => $row->product_id ?? 'deleted:'.$row->name)
            ->map(fn (Collection $group) => [
                'name' => $group->first()->name,
                'unit' => $group->first()->unit === 'weight' ? 'kg' : 'ud',
                'quantity' => (float) $group->sum('quantity'),
                'revenue' => (float) $group->sum('revenue'),
            ]);
    }
}
