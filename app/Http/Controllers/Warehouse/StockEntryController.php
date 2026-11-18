<?php

namespace App\Http\Controllers\Warehouse;

use App\Enums\UnitType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator;
use Illuminate\View\View;

class StockEntryController extends Controller
{
    public function create(Request $request): View
    {
        return view('warehouse.entries.create', [
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku', 'unit_type']),
            'selected' => $request->integer('product') ?: null,
            'recent' => StockMovement::with(['product', 'user'])->latest('id')->limit(10)->get(),
        ]);
    }

    public function store(Request $request, StockService $stock): RedirectResponse
    {
        $request->merge([
            'store_qty' => str_replace(',', '.', (string) $request->input('store_qty', 0)),
            'online_qty' => str_replace(',', '.', (string) $request->input('online_qty', 0)),
        ]);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'store_qty' => ['required', 'numeric', 'min:0', 'max:99999'],
            'online_qty' => ['required', 'numeric', 'min:0', 'max:99999'],
            'reference' => ['nullable', 'string', 'max:60'],
            'reason' => ['nullable', 'string', 'max:255'],
            'lot_code' => ['nullable', 'string', 'max:40'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
        ], [], [
            'product_id' => 'producto', 'store_qty' => 'cantidad para tienda',
            'online_qty' => 'cantidad para online', 'reference' => 'albarán',
            'lot_code' => 'lote', 'expires_at' => 'fecha de caducidad',
        ]);

        $product = Product::findOrFail($data['product_id']);

        // Perishable products are traced by batch and expiry date (proposal, section 5).
        if ($product->is_perishable) {
            $request->validate([
                'lot_code' => ['required'],
                'expires_at' => ['required'],
            ], ['required' => 'El :attribute es obligatorio en productos perecederos.'], ['lot_code' => 'lote', 'expires_at' => 'fecha de caducidad']);
        }

        validator($data)->after(function (Validator $v) use ($data, $product) {
            if ($data['store_qty'] + $data['online_qty'] <= 0) {
                $v->errors()->add('store_qty', 'Indica al menos una cantidad mayor que cero.');
            }
            if ($product->unit_type === UnitType::Unit && (fmod($data['store_qty'], 1) || fmod($data['online_qty'], 1))) {
                $v->errors()->add('store_qty', 'Este producto se vende por unidades: usa cantidades enteras.');
            }
        })->validate();

        $stock->registerEntry(
            $product, (float) $data['store_qty'], (float) $data['online_qty'], $request->user(),
            $data['reference'] ?? null, $data['reason'] ?? null,
            lotCode: isset($data['lot_code']) ? strtoupper(trim($data['lot_code'])) : null,
            expiresAt: $data['expires_at'] ?? null,
        );

        return redirect()->route('warehouse.entries.create')
            ->with('status', "Entrada registrada: {$product->name} (+".$product->formatQuantity($data['store_qty'] + $data['online_qty']).').');
    }
}
