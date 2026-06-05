<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.shop')]
#[Title('Kategori Produk - Compify')]
class extends Component {
    use WithPagination;

    public Category $category;

    public array $selectedBrands = [];
    public array $selectedAvailability = [];
    public array $selectedColors = [];

    public string $sortBy = 'latest';
    public int $perPage = 20;
    public string $viewMode = 'grid';

    public int $minPrice = 0;
    public int $maxPrice = 10000000;

    public int $priceFrom = 0;
    public int $priceTo = 10000000;

    private function categoryIds(): array
    {
        return $this->category->selfAndActiveDescendantIds();
    }

    public function getNavigationCategoriesProperty()
    {
        return $this->category->navigationGroup();
    }

    public function mount(Category $category): void
    {
        $this->category = $category;

        $priceQuery = Product::query()
            ->whereIn('category_id', $this->categoryIds())
            ->where('is_active', true);

        $this->minPrice = (int) ($priceQuery->min('price') ?? 0);
        $this->maxPrice = (int) ($priceQuery->max('price') ?? 10000000);

        $this->priceFrom = $this->minPrice;
        $this->priceTo = $this->maxPrice;
    }

    public function updatedSelectedBrands(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedAvailability(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedColors(): void
    {
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedPriceFrom(): void
    {
        if ($this->priceFrom < $this->minPrice) {
            $this->priceFrom = $this->minPrice;
        }

        if ($this->priceFrom > $this->priceTo) {
            $this->priceFrom = $this->priceTo;
        }

        $this->resetPage();
    }

    public function updatedPriceTo(): void
    {
        if ($this->priceTo > $this->maxPrice) {
            $this->priceTo = $this->maxPrice;
        }

        if ($this->priceTo < $this->priceFrom) {
            $this->priceTo = $this->priceFrom;
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->selectedBrands = [];
        $this->selectedAvailability = [];
        $this->selectedColors = [];
        $this->sortBy = 'latest';
        $this->perPage = 20;
        $this->priceFrom = $this->minPrice;
        $this->priceTo = $this->maxPrice;

        $this->resetPage();
    }

    public function getBrandsProperty()
    {
        return Brand::query()
            ->whereHas('products', function ($query) {
                $query->whereIn('category_id', $this->categoryIds())
                    ->where('is_active', true);
            })
            ->withCount(['products' => function ($query) {
                $query->whereIn('category_id', $this->categoryIds())
                    ->where('is_active', true);
            }])
            ->orderBy('name')
            ->get();
    }

    public function getProductsProperty()
    {
        return Product::query()
            ->with(['brand', 'category'])
            ->whereIn('category_id', $this->categoryIds())
            ->where('is_active', true)
            ->when(count($this->selectedBrands), function ($query) {
                $query->whereIn('brand_id', $this->selectedBrands);
            })
            ->when(in_array('in_stock', $this->selectedAvailability), function ($query) {
                $query->where('stock', '>', 0);
            })
            ->whereBetween('price', [$this->priceFrom, $this->priceTo])
            ->when($this->sortBy === 'price_low', fn($q) => $q->orderBy('price'))
            ->when($this->sortBy === 'price_high', fn($q) => $q->orderByDesc('price'))
            ->when($this->sortBy === 'name_asc', fn($q) => $q->orderBy('name'))
            ->when($this->sortBy === 'name_desc', fn($q) => $q->orderByDesc('name'))
            ->when($this->sortBy === 'latest', fn($q) => $q->latest())
            ->paginate($this->perPage);
    }
};
?>

<div class="category-page">
    <div class="container">

        <div class="category-page__layout">
            {{-- Sidebar --}}
            <aside class="category-sidebar">
                <div class="category-filter">
                    <div class="category-filter__head">
                        <h3>Kategori</h3>
                    </div>

                    <div class="category-filter__body">
                        @foreach($this->navigationCategories as $item)
                            <a
                                href="{{ route('categories.show', $item->slug) }}"
                                class="category-filter__link {{ $item->id === $category->id ? 'is-active' : '' }}"
                                wire:navigate
                            >
                                {{ $item->name }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="category-filter">
                    <div class="category-filter__head">
                        <h3>Range Harga</h3>
                    </div>

                    <div class="category-filter__body">
                        <div class="price-range">
                            <label class="price-range__label">Harga Minimum</label>
                            <input type="range"
                                   min="{{ $minPrice }}"
                                   max="{{ $maxPrice }}"
                                   step="10000"
                                   wire:model.live="priceFrom"
                                   class="price-range__slider">

                            <label class="price-range__label">Harga Maksimum</label>
                            <input type="range"
                                   min="{{ $minPrice }}"
                                   max="{{ $maxPrice }}"
                                   step="10000"
                                   wire:model.live="priceTo"
                                   class="price-range__slider">

                            <div class="price-range__values">
                                <span>Rp {{ number_format($priceFrom, 0, ',', '.') }}</span>
                                <span>Rp {{ number_format($priceTo, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="category-filter">
                    <div class="category-filter__head">
                        <h3>Brand</h3>
                    </div>

                    <div class="category-filter__body">
                        @forelse($this->brands as $brand)
                            <label class="category-filter__checkbox">
                                <input
                                    type="checkbox"
                                    value="{{ $brand->id }}"
                                    wire:model.live="selectedBrands"
                                >
                                <span>{{ $brand->name }} ({{ $brand->products_count }})</span>
                            </label>
                        @empty
                            <p class="category-filter__empty">Belum ada brand.</p>
                        @endforelse
                    </div>
                </div>

                <div class="category-filter">
                    <div class="category-filter__head">
                        <h3>Ketersediaan</h3>
                    </div>

                    <div class="category-filter__body">
                        <label class="category-filter__checkbox">
                            <input type="checkbox" value="in_stock" wire:model.live="selectedAvailability">
                            <span>In Stock</span>
                        </label>
                    </div>
                </div>

                <button type="button" class="category-filter__reset" wire:click="clearFilters">
                    Reset Filter
                </button>
            </aside>

            {{-- Content --}}
            <section class="category-content">

                <div class="category-page__header">
                    <div>
                        <div class="category-page__breadcrumbs">
                            <a href="{{ route('home') }}" wire:navigate>Beranda</a>
                            <span>/</span>
                            <span>Kategori</span>
                            <span>/</span>
                            <span>{{ $category->name }}</span>
                        </div>

                        <h1 class="category-page__title">{{ $category->name }}</h1>
                    </div>
                </div>

                <div class="category-toolbar">
                    <div class="category-toolbar__left">
                        <span class="category-toolbar__label">View As</span>

                        <button
                            type="button"
                            class="category-toolbar__view {{ $viewMode === 'grid' ? 'is-active' : '' }}"
                            wire:click="$set('viewMode', 'grid')"
                        >
                            Grid
                        </button>

                        <button
                            type="button"
                            class="category-toolbar__view {{ $viewMode === 'list' ? 'is-active' : '' }}"
                            wire:click="$set('viewMode', 'list')"
                        >
                            List
                        </button>
                    </div>

                    <div class="category-toolbar__right">
                        <div class="category-toolbar__group">
                            <label>Items Per Page</label>
                            <select wire:model.live="perPage" class="category-toolbar__select">
                                <option value="12">12</option>
                                <option value="20">20</option>
                                <option value="24">24</option>
                                <option value="32">32</option>
                            </select>
                        </div>

                        <div class="category-toolbar__group">
                            <label>Urutkan</label>
                            <select wire:model.live="sortBy" class="category-toolbar__select">
                                <option value="latest">Terbaru</option>
                                <option value="price_low">Harga Terendah</option>
                                <option value="price_high">Harga Tertinggi</option>
                                <option value="name_asc">Nama A-Z</option>
                                <option value="name_desc">Nama Z-A</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="category-result-info">
                    Menampilkan {{ $this->products->firstItem() ?? 0 }}–{{ $this->products->lastItem() ?? 0 }}
                    dari {{ $this->products->total() }} produk
                </div>

                <div class="category-products {{ $viewMode === 'list' ? 'is-list' : '' }}">
                    @forelse($this->products as $product)
                        <x-shop.product-card :product="$product" />
                    @empty
                        <div class="category-empty">
                            <h3>Produk tidak ditemukan</h3>
                            <p>Coba ubah filter atau reset filter.</p>
                        </div>
                    @endforelse
                </div>

                <div class="category-pagination">
                    {{ $this->products->links() }}
                </div>
            </section>
        </div>
    </div>
</div>