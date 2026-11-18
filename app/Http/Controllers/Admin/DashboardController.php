<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reports\DashboardMetrics;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardMetrics $metrics): View
    {
        return view('admin.dashboard.index', [
            'sales' => $metrics->salesToday(),
            'daily' => $metrics->dailySales(14),
            'orders' => $metrics->ordersToday(),
            'pending' => $metrics->pendingToPrepare(),
            'alerts' => $metrics->stockAlerts(),
        ]);
    }
}
