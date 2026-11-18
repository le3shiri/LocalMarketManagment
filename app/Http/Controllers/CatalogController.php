<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::ordered()->whereHas('products', fn ($q) => $q->active())->get();
        $current = $categories->firstWhere('slug', $request->query('categoria'));

        $products = Product::active()
            ->with(['category', 'stock'])
            ->when($current, fn ($q) => $q->whereBelongsTo($current))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('catalog.index', compact('categories', 'current', 'products'));
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        return view('catalog.show', ['product' => $product->load(['category', 'stock'])]);
    }
}
