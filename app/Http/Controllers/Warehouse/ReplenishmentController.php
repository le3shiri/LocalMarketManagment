<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Minimum stock levels and the reorder list built from them.
 */
class ReplenishmentController extends Controller
{
    /** Products below their minimum, with how much is missing and a suggested order. */
    public function index(): View
    {
        $stocks = ProductStock::belowMinimum()
            ->with('product.category')
            ->get()
            ->sortByDesc(fn (ProductStock $s) => $s->min_alert > 0 ? ($s->min_alert - $s->on_hand) / $s->min_alert : 0)
            ->values();

        return view('warehouse.replenishment.index', compact('stocks'));
    }

    public function updateMinimum(Request $request, Product $product): RedirectResponse
    {
        $request->merge(['min_alert' => str_replace(',', '.', (string) $request->input('min_alert'))]);
        // Products sold by the unit need a whole-number minimum; weights can use decimals.
        $data = $request->validate([
            'min_alert' => ['required', 'numeric', 'min:0', 'max:99999', ...($product->unit_type->step() >= 1 ? ['integer'] : [])],
        ], [], ['min_alert' => 'mínimo']);

        $stock = $product->stock;
        $before = $stock->min_alert;
        $stock->update(['min_alert' => round((float) $data['min_alert'], 3)]);

        AuditLog::record('stock', "Mínimo de «{$product->name}»: {$product->formatQuantity($before)} → {$product->formatQuantity($stock->min_alert)}", $product);

        return back()->with('status', "Mínimo de «{$product->name}» actualizado.");
    }

    /** Order enough to get back to twice the minimum (simple rule, adjustable later). */
    public static function suggestedOrder(ProductStock $stock): float
    {
        return max(0, round(2 * $stock->min_alert - $stock->on_hand, 3));
    }
}
