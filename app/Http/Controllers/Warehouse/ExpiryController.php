<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\StockBatch;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Batches that should leave the shop first: those about to expire.
 */
class ExpiryController extends Controller
{
    public function index(Request $request): View
    {
        $days = min(30, max(0, $request->integer('dias', (int) config('shop.expiry_warning_days'))));

        $batches = StockBatch::with('product.stock')
            ->where('status', StockBatch::ACTIVE)
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '>=', today())
            ->whereDate('expires_at', '<=', today()->addDays($days))
            ->orderBy('expires_at')
            ->get();

        return view('warehouse.expiry.index', compact('batches', 'days'));
    }
}
