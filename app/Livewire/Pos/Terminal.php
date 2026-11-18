<?php

namespace App\Livewire\Pos;

use App\Models\Product;
use App\Support\SaleTotals;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Counter sales terminal (TPV). The search box is always focused: a barcode scanner
 * types the code and presses Enter, which adds the product straight away.
 */
#[Layout('components.layouts.app')]
#[Title('Caja')]
class Terminal extends Component
{
    public string $search = '';

    /** @var array<int, float> product_id => quantity */
    public array $lines = [];

    public ?string $message = null;

    #[Computed]
    public function results(): Collection
    {
        $term = trim($this->search);

        if (mb_strlen($term) < 2 || ctype_digit($term) && strlen($term) >= 8) {
            return collect(); // looks like a barcode being scanned: wait for Enter
        }

        return Product::active()
            ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"))
            ->orderBy('name')->limit(8)->get();
    }

    /** Enter in the search box: exact barcode or SKU adds the product, otherwise the single match. */
    public function scan(): void
    {
        $term = trim($this->search);

        if ($term === '') {
            return;
        }

        $product = Product::active()->where('barcode', $term)->orWhere(fn ($q) => $q->active()->where('sku', strtoupper($term)))->first()
            ?? ($this->results->count() === 1 ? $this->results->first() : null);

        if (! $product) {
            $this->message = "No se ha encontrado ningún producto con «{$term}».";

            return;
        }

        $this->add($product->id);
    }

    public function add(int $productId): void
    {
        $product = Product::active()->findOrFail($productId);

        $this->lines[$product->id] = round(($this->lines[$product->id] ?? 0) + $product->unit_type->step(), 3);
        $this->search = '';
        $this->message = null;
        unset($this->results);
        $this->dispatch('focus-search');
    }

    /** Quantity typed by the cashier, or the weight read on the scale for loose products. */
    public function setQuantity(int $productId, $quantity): void
    {
        $quantity = round((float) str_replace(',', '.', (string) $quantity), 3);

        if (! isset($this->lines[$productId])) {
            return;
        }

        $product = Product::findOrFail($productId);

        // Units must be whole; weights are free (whatever the scale shows).
        if ($product->unit_type->step() >= 1 && fmod($quantity, 1) !== 0.0) {
            $this->message = "«{$product->name}» se vende por unidades.";

            return;
        }

        if ($quantity <= 0) {
            unset($this->lines[$productId]);
        } else {
            $this->lines[$productId] = $quantity;
        }

        $this->message = null;
        $this->dispatch('focus-search');
    }

    public function removeLine(int $productId): void
    {
        unset($this->lines[$productId]);
        $this->dispatch('focus-search');
    }

    public function clear(): void
    {
        $this->reset('lines', 'search', 'message');
        $this->dispatch('focus-search');
    }

    #[Computed]
    public function totals(): SaleTotals
    {
        $products = Product::whereIn('id', array_keys($this->lines))->get()->keyBy('id');

        return SaleTotals::for(
            collect($this->lines)
                ->filter(fn ($quantity, $id) => isset($products[$id]))
                ->map(fn ($quantity, $id) => ['product' => $products[$id], 'quantity' => (float) $quantity])
                ->values()
        );
    }

    public function render(): View
    {
        unset($this->totals);

        return view('livewire.pos.terminal');
    }
}
