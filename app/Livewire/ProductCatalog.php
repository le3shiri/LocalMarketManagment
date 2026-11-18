<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Catalog grid with live search by name/description and category filter.
 * Both filters live in the query string so results can be shared and bookmarked.
 */
class ProductCatalog extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'categoria', except: '')]
    public string $category = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function selectCategory(string $slug): void
    {
        $this->category = $slug;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'category');
        $this->resetPage();
    }

    #[Computed]
    public function categories()
    {
        return Category::ordered()->whereHas('products', fn ($q) => $q->active())->get();
    }

    #[Computed]
    public function currentCategory(): ?Category
    {
        return $this->categories->firstWhere('slug', $this->category);
    }

    public function render(): View
    {
        $term = trim($this->search);

        $products = Product::active()
            ->with(['category', 'stock'])
            ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")))
            ->when($this->currentCategory, fn ($q, $category) => $q->whereBelongsTo($category))
            ->orderBy('name')
            ->paginate(24);

        return view('livewire.product-catalog', compact('products'));
    }
}
