<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /** The grid, search and filters live in the ProductCatalog Livewire component. */
    public function index(): View
    {
        return view('catalog.index');
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        return view('catalog.show', ['product' => $product->load(['category', 'stock'])]);
    }
}
