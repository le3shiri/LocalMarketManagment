<?php

namespace App\Http\Controllers\Warehouse;

use App\Enums\MovementType;
use App\Enums\UnitType;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Services\ChannelAllocator;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Store / online split: one-off transfers and the owner's default shares.
 */
class ChannelController extends Controller
{
    public function create(Request $request): View
    {
        return view('warehouse.channels.index', [
            'products' => Product::with('stock')->orderBy('name')->get(),
            'selected' => $request->integer('product') ?: null,
            'categories' => Category::ordered()->get(),
            'share' => (int) Setting::get('online_share'),
            'shareFresh' => (int) Setting::get('online_share_fresh'),
            'recent' => StockMovement::with(['product', 'user'])->where('type', MovementType::Transfer)->latest('id')->limit(10)->get(),
        ]);
    }

    public function transfer(Request $request, StockService $stock): RedirectResponse
    {
        $request->merge(['quantity' => str_replace(',', '.', (string) $request->input('quantity'))]);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'to' => ['required', Rule::in(['online', 'store'])],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:99999'],
            'reason' => ['required', 'string', 'min:3', 'max:200'],
        ], [], ['product_id' => 'producto', 'to' => 'destino', 'quantity' => 'cantidad', 'reason' => 'motivo']);

        $product = Product::findOrFail($data['product_id']);
        $quantity = (float) $data['quantity'];

        if ($product->unit_type === UnitType::Unit && fmod($quantity, 1) !== 0.0) {
            return back()->withInput()->withErrors(['quantity' => 'Este producto se mueve por unidades enteras.']);
        }

        try {
            $stock->transfer($product, $quantity, $data['to'], $request->user(), $data['reason']);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['quantity' => $e->getMessage()]);
        }

        $direction = $data['to'] === 'online' ? 'tienda → online' : 'online → tienda';
        AuditLog::record('stock', "Transferencia de {$product->formatQuantity($quantity)} de «{$product->name}» ({$direction})", $product, ['motivo' => $data['reason']]);

        return back()->with('status', "Transferidos {$product->formatQuantity($quantity)} de «{$product->name}» ({$direction}).");
    }

    public function updateShares(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'online_share' => ['required', 'integer', 'between:0,100'],
            'online_share_fresh' => ['required', 'integer', 'between:0,100'],
        ], [], ['online_share' => 'porcentaje online', 'online_share_fresh' => 'porcentaje online de frescos']);

        Setting::set('online_share', (string) $data['online_share']);
        Setting::set('online_share_fresh', (string) $data['online_share_fresh']);

        AuditLog::record('stock', "Reparto online: {$data['online_share']} % general, {$data['online_share_fresh']} % frescos");

        return back()->with('status', 'Porcentajes del canal online guardados.');
    }

    public function rebalance(Request $request, ChannelAllocator $allocator): RedirectResponse
    {
        $data = $request->validate(['category_id' => ['nullable', 'exists:categories,id']]);
        $category = isset($data['category_id']) ? Category::find($data['category_id']) : null;

        $changed = $allocator->rebalance($request->user(), $category);

        AuditLog::record('stock', 'Reparto automático aplicado'.($category ? " a «{$category->name}»" : ' a todo el catálogo')." ({$changed} productos)");

        return back()->with('status', "Reparto aplicado: {$changed} productos ajustados.");
    }
}
