<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\View\View;

class BatchController extends Controller
{
    /** Batch traceability of one product: what came in, when it expires, what is left. */
    public function index(Product $product): View
    {
        return view('warehouse.batches.index', [
            'product' => $product->load('stock'),
            'batches' => $product->batches()->with('receiver')
                ->orderByRaw('quantity_remaining = 0')
                ->orderByRaw('expires_at is null')
                ->orderBy('expires_at')
                ->latest('id')
                ->get(),
        ]);
    }
}
