<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $stocks = ProductStock::query()
            ->select('product_stocks.*')
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->with('product.category')
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('products.name', 'like', "%{$term}%")
                ->orWhere('products.sku', 'like', "%{$term}%")))
            ->when($request->integer('category'), fn ($q, $id) => $q->where('products.category_id', $id))
            ->when($request->boolean('low'), fn ($q) => $q->belowMinimum())
            ->orderBy('products.name')
            ->paginate(25)
            ->withQueryString();

        return view('warehouse.stock.index', [
            'stocks' => $stocks,
            'categories' => Category::ordered()->get(),
            'lowCount' => ProductStock::belowMinimum()->count(),
        ]);
    }
}
