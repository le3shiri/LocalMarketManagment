<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reports\DashboardMetrics;
use App\Services\Reports\TopProducts;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardMetrics $metrics, TopProducts $top): View
    {
        return view('admin.dashboard.index', [
            'topProducts' => $top->build(CarbonImmutable::today()->subDays(29), CarbonImmutable::today(), limit: 5),
            'sales' => $metrics->salesToday(),
            'daily' => $metrics->dailySales(14),
            'orders' => $metrics->ordersToday(),
            'pending' => $metrics->pendingToPrepare(),
            'alerts' => $metrics->stockAlerts(),
        ]);
    }
}
