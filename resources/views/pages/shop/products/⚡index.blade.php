<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.shop')]
#[Title('Produk - Compify')]
class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $brand = '';

    #[Url]
    public string $sort = 'latest';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedBrand(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function categories()
    {
        return Category::active()->orderBy('sort_order')->get();
    }

    #[Computed]
    public function brands()
    {
        return Brand::active()->orderBy('name')->get();
    }

    #[Computed]
    public function products()
    {
        $search = trim($this->search);
        $searchCategoryIds = Category::searchIdsWithActiveDescendants($search);

        $selectedCategory = $this->category
            ? Category::query()->active()->where('slug', $this->category)->first()
            : null;

        return Product::with(['category.parent', 'brand'])
            ->active()
            ->when($search !== '', function ($query) use ($search, $searchCategoryIds) {
                $like = '%' . $search . '%';

                $query->where(function ($q) use ($like, $searchCategoryIds) {
                    $q->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like)
                        ->orWhereHas('category', function ($categoryQuery) use ($like) {
                            $categoryQuery->where('name', 'like', $like)
                                ->orWhere('slug', 'like', $like);
                        })
                        ->orWhereHas('category.parent', function ($parentQuery) use ($like) {
                            $parentQuery->where('name', 'like', $like)
                                ->orWhere('slug', 'like', $like);
                        });

                    if ($searchCategoryIds !== []) {
                        $q->orWhereIn('category_id', $searchCategoryIds);
                    }
                });
            })
            ->when($selectedCategory, function ($query) use ($selectedCategory) {
                $query->whereIn('category_id', $selectedCategory->selfAndActiveDescendantIds());
            })
            ->when($this->brand, function ($query) {
                $query->whereHas('brand', fn ($q) => $q->where('slug', $this->brand));
            })
            ->when($this->sort === 'price_low', fn ($query) => $query->orderBy('price'))
            ->when($this->sort === 'price_high', fn ($query) => $query->orderByDesc('price'))
            ->when($this->sort === 'latest', fn ($query) => $query->latest())
            ->paginate(12);
    }
};
?>

<section class="section">
    <div class="section-title">
        <h2>Semua Produk</h2>
    </div>

    <div class="filter-bar">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari produk, SKU, kategori, atau subkategori...">

        <select wire:model.live="category">
            <option value="">Semua Kategori</option>
            @foreach($this->categories as $item)
                <option value="{{ $item->slug }}">{{ $item->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="brand">
            <option value="">Semua Merk</option>
            @foreach($this->brands as $item)
                <option value="{{ $item->slug }}">{{ $item->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="sort">
            <option value="latest">Terbaru</option>
            <option value="price_low">Harga Termurah</option>
            <option value="price_high">Harga Termahal</option>
        </select>
    </div>

    <div class="product-grid">
        @forelse($this->products as $product)
            <x-product-card :product="$product" />
        @empty
            <p>Produk tidak ditemukan.</p>
        @endforelse
    </div>

    <div style="margin-top: 34px;">
        {{ $this->products->links() }}
    </div>
</section>
