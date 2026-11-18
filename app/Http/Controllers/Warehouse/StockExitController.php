<?php

namespace App\Http\Controllers\Warehouse;

use App\Enums\ExitReason;
use App\Enums\MovementType;
use App\Enums\UnitType;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class StockExitController extends Controller
{
    public function create(Request $request): View
    {
        return view('warehouse.exits.create', [
            'products' => Product::with('stock')->orderBy('name')->get(),
            'selected' => $request->integer('product') ?: null,
            'reasons' => ExitReason::cases(),
            'recent' => StockMovement::with(['product', 'user'])->where('type', MovementType::Exit)->latest('id')->limit(10)->get(),
        ]);
    }

    public function store(Request $request, StockService $stock): RedirectResponse
    {
        $request->merge(['quantity' => str_replace(',', '.', (string) $request->input('quantity'))]);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'channel' => ['required', Rule::in(['store', 'online'])],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:99999'],
            'reason' => ['required', Rule::enum(ExitReason::class)],
            'notes' => [Rule::requiredIf($request->input('reason') === ExitReason::Other->value), 'nullable', 'string', 'max:200'],
            'reference' => ['nullable', 'string', 'max:60'],
        ], [], ['product_id' => 'producto', 'channel' => 'origen', 'quantity' => 'cantidad', 'reason' => 'motivo', 'notes' => 'detalle', 'reference' => 'referencia']);

        $product = Product::findOrFail($data['product_id']);
        $quantity = (float) $data['quantity'];

        if ($product->unit_type === UnitType::Unit && fmod($quantity, 1) !== 0.0) {
            return back()->withInput()->withErrors(['quantity' => 'Este producto se mueve por unidades enteras.']);
        }

        $reason = ExitReason::from($data['reason'])->label().(! empty($data['notes']) ? ': '.$data['notes'] : '');

        try {
            $stock->removeStock($product, $quantity, $data['channel'], MovementType::Exit, $request->user(), $reason, $data['reference'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['quantity' => $e->getMessage()]);
        }

        AuditLog::record('stock', "Salida de {$product->formatQuantity($quantity)} de «{$product->name}» ({$reason})", $product, ['origen' => $data['channel']]);

        return redirect()->route('warehouse.exits.create')->with('status', "Salida registrada: {$product->name} (−{$product->formatQuantity($quantity)}).");
    }
}
