<?php

namespace App\Services\Reports;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductStock;
use App\Models\StockBatch;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Key figures for the owner's dashboard. "Sales" means money actually taken: counter
 * tickets plus orders paid on delivery, minus refunds (all live in payments).
 */
class DashboardMetrics
{
    public function salesOn(CarbonImmutable $day): float
    {
        return round((float) Payment::whereBetween('created_at', [$day->startOfDay(), $day->endOfDay()])->sum('amount'), 2);
    }

    /** @return array{today: float, lastWeek: float, change: ?float} */
    public function salesToday(): array
    {
        $today = $this->salesOn(CarbonImmutable::today());
        $lastWeek = $this->salesOn(CarbonImmutable::today()->subWeek());

        return [
            'today' => $today,
            'lastWeek' => $lastWeek,
            'change' => $lastWeek > 0 ? round(100 * ($today - $lastWeek) / $lastWeek, 1) : null,
        ];
    }

    /** @return Collection<string, float> 'Y-m-d' => amount, oldest first */
    public function dailySales(int $days = 14): Collection
    {
        $from = CarbonImmutable::today()->subDays($days - 1);

        $totals = Payment::where('created_at', '>=', $from)
            ->selectRaw('date(created_at) as day, sum(amount) as amount')
            ->groupBy('day')
            ->pluck('amount', 'day');

        return collect(range(0, $days - 1))
            ->mapWithKeys(fn (int $i) => [$from->addDays($i)->toDateString() => round((float) ($totals[$from->addDays($i)->toDateString()] ?? 0), 2)]);
    }

    /** @return array{byStatus: Collection, byChannel: Collection, total: int} */
    public function ordersToday(): array
    {
        $today = Order::whereDate('created_at', today());

        return [
            'byStatus' => (clone $today)->selectRaw('status, count(*) as how_many')->groupBy('status')->pluck('how_many', 'status'),
            'byChannel' => (clone $today)->selectRaw('channel, count(*) as how_many')->groupBy('channel')->pluck('how_many', 'channel'),
            'total' => (clone $today)->count(),
        ];
    }

    /** Orders due today or earlier that nobody has started preparing. */
    public function pendingToPrepare(): int
    {
        return Order::where('status', OrderStatus::Pending)->whereDate('slot_date', '<=', today())->count();
    }

    /** @return array{lowStock: int, expiringSoon: int, expired: int} */
    public function stockAlerts(): array
    {
        return [
            'lowStock' => ProductStock::belowMinimum()->count(),
            'expiringSoon' => StockBatch::where('status', StockBatch::ACTIVE)->where('quantity_remaining', '>', 0)
                ->whereBetween('expires_at', [today(), today()->addDays((int) config('shop.expiry_warning_days'))])->count(),
            'expired' => StockBatch::where('quantity_remaining', '>', 0)
                ->where(fn ($q) => $q->where('status', StockBatch::EXPIRED)
                    ->orWhere(fn ($q) => $q->where('status', StockBatch::ACTIVE)->whereDate('expires_at', '<', today())))
                ->count(),
        ];
    }
}
