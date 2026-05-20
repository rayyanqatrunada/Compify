<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class ProductCatalog extends Component
{
    use WithPagination;

    public string $search = '';

    public string $category = '';

    public string $sort = 'featured';

    public string $stock = '';

    public function mount(): void
    {
        $this->search = (string) request()->query('search', '');
        $this->category = (string) request()->query('category', '');
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'category', 'sort', 'stock'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'category', 'sort', 'stock']);
        $this->resetPage();
    }

    public function render()
    {
        $products = Product::query()
            ->active()
            ->with('category')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('short_description', 'like', '%'.$this->search.'%')
                        ->orWhere('sku', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->category, function ($query) {
                $query->whereHas('category', fn ($query) => $query->where('slug', $this->category));
            })
            ->when($this->stock === 'ready', fn ($query) => $query->where('stock', '>', 0))
            ->when($this->stock === 'low', fn ($query) => $query->whereBetween('stock', [1, 10]))
            ->when($this->sort === 'newest', fn ($query) => $query->latest())
            ->when($this->sort === 'price-low', fn ($query) => $query->orderBy('price'))
            ->when($this->sort === 'price-high', fn ($query) => $query->orderByDesc('price'))
            ->when($this->sort === 'popular', fn ($query) => $query->orderByDesc('sold_count'))
            ->when($this->sort === 'featured', fn ($query) => $query->orderByDesc('is_featured')->latest())
            ->paginate(9);

        return view('livewire.product-catalog', [
            'products' => $products,
            'categories' => Category::query()
                ->where('is_active', true)
                ->withCount(['products' => fn ($query) => $query->active()])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
