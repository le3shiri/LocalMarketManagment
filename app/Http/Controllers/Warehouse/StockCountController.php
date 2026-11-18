<?php

namespace App\Http\Controllers\Warehouse;

use App\Enums\MovementType;
use App\Enums\UnitType;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Physical stock count: the employee types what is really on the shelves and the system
 * records the difference with a mandatory reason.
 */
class StockCountController extends Controller
{
    public function create(Request $request): View
    {
        return view('warehouse.counts.create', [
            'products' => Product::with('stock')->orderBy('name')->get(),
            'selected' => $request->integer('product') ?: null,
            'recent' => StockMovement::with(['product', 'user'])->where('type', MovementType::Adjustment)->latest('id')->limit(10)->get(),
        ]);
    }

    public function store(Request $request, StockService $stock): RedirectResponse
    {
        $request->merge(['counted' => str_replace(',', '.', (string) $request->input('counted'))]);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'counted' => ['required', 'numeric', 'min:0', 'max:99999'],
            'reason' => ['required', 'string', 'min:3', 'max:200'],
        ], [], ['product_id' => 'producto', 'counted' => 'cantidad contada', 'reason' => 'motivo']);

        $product = Product::findOrFail($data['product_id']);
        $counted = (float) $data['counted'];

        if ($product->unit_type === UnitType::Unit && fmod($counted, 1) !== 0.0) {
            return back()->withInput()->withErrors(['counted' => 'Este producto se cuenta por unidades enteras.']);
        }

        try {
            $difference = $stock->adjustCount($product, $counted, $request->user(), $data['reason']);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['counted' => $e->getMessage()]);
        }

        if ($difference === 0.0) {
            return back()->with('status', "«{$product->name}»: el recuento coincide con el sistema, no hay nada que ajustar.");
        }

        $signed = ($difference > 0 ? '+' : '').$product->formatQuantity($difference);
        AuditLog::record('stock', "Ajuste por recuento de «{$product->name}»: {$signed}", $product, ['motivo' => $data['reason']], sensitive: true);

        return redirect()->route('warehouse.counts.create')->with('status', "Ajuste registrado: {$product->name} ({$signed}).");
    }
}
