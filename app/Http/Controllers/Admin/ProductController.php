<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UnitType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::with('category')
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")))
            ->when($request->integer('category'), fn ($q, $id) => $q->where('category_id', $id))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', $this->formData(new Product));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = new Product($request->productData());

        if ($request->hasFile('image')) {
            $product->image_path = $request->file('image')->store('products', 'public');
        }

        $product->save();

        return redirect()->route('admin.products.index')->with('status', "Producto «{$product->name}» creado.");
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', $this->formData($product));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->fill($request->productData());

        if ($request->hasFile('image')) {
            $previous = $product->image_path;
            $product->image_path = $request->file('image')->store('products', 'public');
        }

        $product->save();

        // Only remove the old file once the new one is safely referenced.
        if (isset($previous)) {
            Storage::disk('public')->delete($previous);
        }

        return redirect()->route('admin.products.index')->with('status', "Producto «{$product->name}» actualizado.");
    }

    public function toggle(Product $product): RedirectResponse
    {
        $product->forceFill(['is_active' => ! $product->is_active])->save();

        return back()->with('status', $product->is_active
            ? "«{$product->name}» vuelve a estar a la venta."
            : "«{$product->name}» se ha retirado de la venta.");
    }

    private function formData(Product $product): array
    {
        return [
            'product' => $product,
            'categories' => Category::ordered()->get(),
            'unitTypes' => UnitType::cases(),
        ];
    }
}
