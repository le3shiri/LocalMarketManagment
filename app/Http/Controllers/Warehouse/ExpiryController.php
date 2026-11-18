<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\StockBatch;
use App\Services\ExpiryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Batches that should leave the shop first (about to expire) and expired batches waiting
 * to be physically withdrawn.
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

        $expired = StockBatch::with('product')
            ->where('quantity_remaining', '>', 0)
            ->where(fn ($q) => $q->where('status', StockBatch::EXPIRED)
                ->orWhere(fn ($q) => $q->where('status', StockBatch::ACTIVE)->whereDate('expires_at', '<', today())))
            ->orderBy('expires_at')
            ->get();

        return view('warehouse.expiry.index', compact('batches', 'days', 'expired'));
    }

    /** "Procesar caducados ahora": same as the nightly job. */
    public function block(Request $request, ExpiryService $expiry): RedirectResponse
    {
        $count = $expiry->blockExpired($request->user())->count();

        return back()->with('status', $count ? "{$count} lote(s) caducado(s) retirados de la venta." : 'No había lotes caducados pendientes.');
    }

    public function withdraw(Request $request, StockBatch $batch, ExpiryService $expiry): RedirectResponse
    {
        if ($batch->status !== StockBatch::EXPIRED) {
            return back()->with('error', 'Solo se puede confirmar la retirada de lotes ya bloqueados por caducidad.');
        }

        $expiry->confirmWithdrawal($batch, $request->user());

        return back()->with('status', "Retirada del lote {$batch->lot_code} confirmada.");
    }
}
