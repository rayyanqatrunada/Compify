<?php

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\HomeSection;

new
#[Layout('layouts.shop')]
#[Title('Compify - Toko Perlengkapan Komputer')]
class extends Component {
    public int $bannerIndex = 0;
    public int $newProductIndex = 0;
    public int $featuredProductIndex = 0;

    #[Computed]
    public function banners()
    {
        return Banner::active()->orderBy('sort_order')->get();
    }

    #[Computed]
    public function currentBanner()
    {
        return $this->banners->get($this->bannerIndex) ?? $this->banners->first();
    }

    #[Computed]
    public function categories()
    {
        return Category::active()->orderBy('sort_order')->limit(8)->get();
    }

    #[Computed]
    public function newProducts()
    {
        $products = Product::with(['category', 'brand'])
            ->active()
            ->where('is_new', true)
            ->latest()
            ->limit(12)
            ->get();

        if ($products->isEmpty()) {
            return Product::with(['category', 'brand'])
                ->active()
                ->latest()
                ->limit(12)
                ->get();
        }

        return $products;
    }

    #[Computed]
    public function featuredProducts()
    {
        return Product::with(['category', 'brand'])
            ->active()
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->latest()
            ->limit(12)
            ->get();
    }

    #[Computed]
    public function featuredMain()
    {
        return $this->featuredProducts->get(0);
    }

    #[Computed]
    public function featuredSecond()
    {
        return $this->featuredProducts->get(1) ?? $this->featuredProducts->get(0);
    }

    #[Computed]
    public function featuredThird()
    {
        return $this->featuredProducts->get(2) ?? $this->featuredSecond;
    }

    #[Computed]
    public function featuredFourth()
    {
        return $this->featuredProducts->get(3) ?? $this->featuredMain;
    }

    public function nextFeaturedProducts(): void
    {
        $count = $this->featuredProducts->count();

        if ($count > 4) {
            $this->featuredProductIndex = ($this->featuredProductIndex + 1) % max(1, $count - 3);
        }
    }

    public function prevFeaturedProducts(): void
    {
        $count = $this->featuredProducts->count();

        if ($count > 4) {
            $this->featuredProductIndex = ($this->featuredProductIndex - 1 + max(1, $count - 3)) % max(1, $count - 3);
        }
    }

    public function nextBanner(): void
    {
        $count = $this->banners->count();

        if ($count > 0) {
            $this->bannerIndex = ($this->bannerIndex + 1) % $count;
        }
    }

    public function prevBanner(): void
    {
        $count = $this->banners->count();

        if ($count > 0) {
            $this->bannerIndex = ($this->bannerIndex - 1 + $count) % $count;
        }
    }

    public array $sectionIndexes = [];
    public array $galleryIndexes = [];

    #[Computed]
    public function homeSections()
    {
        return HomeSection::with(['category.children', 'product'])
            ->active()
            ->orderBy('sort_order')
            ->get();
    }

    public function productsForSection(HomeSection $section)
    {
        $categoryIds = collect();

        if ($section->category_id) {
            $categoryIds->push($section->category_id);

            if ($section->category) {
                $categoryIds = $categoryIds->merge(
                    $section->category->children()->pluck('id')
                );
            }
        }

        $query = Product::with(['category', 'brand'])
            ->active()
            ->orderBy('sort_order')
            ->latest();

        if ($categoryIds->isNotEmpty()) {
            $query->whereIn('category_id', $categoryIds->unique()->values());
        }

        $index = $this->sectionIndexes[$section->id] ?? 0;

        return $query->skip($index)->take(4)->get();
    }

    public function productCountForSection(HomeSection $section): int
    {
        $categoryIds = collect();

        if ($section->category_id) {
            $categoryIds->push($section->category_id);

            if ($section->category) {
                $categoryIds = $categoryIds->merge(
                    $section->category->children()->pluck('id')
                );
            }
        }

        $query = Product::active();

        if ($categoryIds->isNotEmpty()) {
            $query->whereIn('category_id', $categoryIds->unique()->values());
        }

        return $query->count();
    }

    public function nextSectionProducts(int $sectionId): void
    {
        $section = HomeSection::with('category.children')->find($sectionId);

        if (! $section) {
            return;
        }

        $count = $this->productCountForSection($section);
        $maxIndex = max(0, $count - 4);

        $current = $this->sectionIndexes[$sectionId] ?? 0;
        $this->sectionIndexes[$sectionId] = $current >= $maxIndex ? 0 : $current + 1;
    }

    public function prevSectionProducts(int $sectionId): void
    {
        $section = HomeSection::with('category.children')->find($sectionId);

        if (! $section) {
            return;
        }

        $count = $this->productCountForSection($section);
        $maxIndex = max(0, $count - 4);

        $current = $this->sectionIndexes[$sectionId] ?? 0;
        $this->sectionIndexes[$sectionId] = $current <= 0 ? $maxIndex : $current - 1;
    }

    public function galleryImages(HomeSection $section): array
    {
        $images = collect([
            $section->image,
            $section->image_2,
            $section->image_3,
        ])->filter()->values()->all();

        if (empty($images) && $section->product?->image) {
            $images[] = $section->product->image;
        }

        if (count($images) <= 1) {
            return $images;
        }

        $index = $this->galleryIndexes[$section->id] ?? 0;

        return array_values(array_merge(
            array_slice($images, $index),
            array_slice($images, 0, $index)
        ));
    }

    public function nextGallery(int $sectionId): void
    {
        $section = HomeSection::find($sectionId);

        if (! $section) {
            return;
        }

        $count = count($this->galleryImages($section));

        if ($count <= 1) {
            return;
        }

        $current = $this->galleryIndexes[$sectionId] ?? 0;
        $this->galleryIndexes[$sectionId] = ($current + 1) % $count;
    }

    public function prevGallery(int $sectionId): void
    {
        $section = HomeSection::find($sectionId);

        if (! $section) {
            return;
        }

        $count = count($this->galleryImages($section));

        if ($count <= 1) {
            return;
        }

        $current = $this->galleryIndexes[$sectionId] ?? 0;
        $this->galleryIndexes[$sectionId] = ($current - 1 + $count) % $count;
    }
};
?>

<div>
    @php
        $banner = $this->currentBanner;
        $heroImage = $banner?->image ? Storage::url($banner->image) : null;
    @endphp

    <section
        class="hero-slider"
        @if($this->banners->count() > 1)
            wire:poll.6000ms="nextBanner"
        @endif
    >
        <div
            class="hero-slide"
            @if($heroImage)
                style="background-image: linear-gradient(90deg, rgba(0,0,0,.82), rgba(0,0,0,.42), rgba(0,0,0,.12)), url('{{ $heroImage }}')"
            @endif
            wire:key="banner-{{ $banner?->id ?? 'default' }}"
        >
            <div class="hero-copy">
                <p class="hero-eyebrow">Compify Computer Store</p>

                <h1>{{ $banner?->title ?? 'Build PC Impianmu di Compify' }}</h1>

                <p class="hero-support">
                    {{ $banner?->subtitle ?? 'Temukan motherboard, PSU, RAM, SSD, casing, dan perlengkapan komputer terbaik.' }}
                </p>

                <a href="{{ $banner?->button_url ?? route('products.index') }}" class="hero-button" wire:navigate>
                    {{ $banner?->button_text ?? 'Belanja Sekarang' }}
                </a>
            </div>

            @if($this->banners->count() > 1)
                <div class="hero-controls">
                    <button type="button" wire:click="prevBanner">‹</button>

                    <div class="hero-dots">
                        @foreach($this->banners as $index => $item)
                            <button
                                type="button"
                                wire:click="$set('bannerIndex', {{ $index }})"
                                @class(['active' => $bannerIndex === $index])
                                aria-label="Banner {{ $index + 1 }}"
                            ></button>
                        @endforeach
                    </div>

                    <button type="button" wire:click="nextBanner">›</button>
                </div>
            @endif
        </div>
    </section>

    <section class="home-category-strip">
        <div class="home-category-strip-head">
            <p>Kategori</p>
            <a href="{{ route('products.index') }}" wire:navigate>Lihat Semua ></a>
        </div>

        <div class="home-category-list">
            @foreach($this->categories as $category)
                <a href="{{ route('categories.show', $category) }}" wire:navigate>
                    <strong>{{ $category->name }}</strong>
                    <span>{{ $category->children->count() }} subkategori</span>
                </a>
            @endforeach
        </div>
    </section>

    @foreach($this->homeSections as $section)
        @if($section->section_type === 'category_products')
            @php
                $products = $this->productsForSection($section);
            @endphp

            <section class="home-category-products-section">
                <div class="home-display-head">
                    <div>
                        <p>{{ $section->subtitle ?: 'Display Produk' }}</p>
                        <h2>{{ $section->title ?: $section->category?->name }}</h2>
                    </div>

                    <div class="home-display-actions">
                        <button type="button" wire:click="prevSectionProducts({{ $section->id }})">‹</button>
                        <button type="button" wire:click="nextSectionProducts({{ $section->id }})">›</button>

                        @if($section->category)
                            <a href="{{ route('categories.show', $section->category) }}" wire:navigate>
                                Lihat Semua >
                            </a>
                        @endif
                    </div>
                </div>

                <div class="product-grid modern-product-grid">
                    @forelse($products as $product)
                        <x-product-card :product="$product" />
                    @empty
                        <div class="empty-state">
                            <h3>Belum ada produk</h3>
                            <p>Tambahkan produk ke kategori ini dari admin.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        @endif

        @if($section->section_type === 'story')
            @php
                $storyImage = $section->image ? Storage::url($section->image) : null;
                $buttonUrl = $section->button_url ?: ($section->product ? route('products.show', $section->product) : route('products.index'));
            @endphp

            <section class="home-story-section {{ $section->image_position === 'left' ? 'image-left' : 'image-right' }} {{ $loop->first ? 'story-hero-full' : '' }}">
                <div class="home-story-copy">
                    @if($section->subtitle)
                        <p>{{ $section->subtitle }}</p>
                    @endif

                    <h2>{{ $section->title ?: $section->product?->name }}</h2>

                    <div class="home-story-description">
                        {!! nl2br(e($section->description ?: $section->product?->description)) !!}
                    </div>

                    <a href="{{ $buttonUrl }}" wire:navigate>
                        {{ $section->button_text ?: 'Learn More' }}
                    </a>
                </div>

                <div class="home-story-image">
                    @if($storyImage)
                        <img src="{{ $storyImage }}" alt="{{ $section->title }}">
                    @elseif($section->product?->image)
                        <img src="{{ Storage::url($section->product->image) }}" alt="{{ $section->product->name }}">
                    @else
                        <span>{{ strtoupper(substr($section->title ?: 'CP', 0, 2)) }}</span>
                    @endif
                </div>
            </section>
        @endif

        @if($section->section_type === 'gallery')
            @php
                $images = $this->galleryImages($section);
                $mainImage = $images[0] ?? null;
                $sideImageOne = $images[1] ?? $mainImage;
                $sideImageTwo = $images[2] ?? $mainImage;

                $galleryUrl = $section->button_url ?: ($section->product ? route('products.show', $section->product) : route('products.index'));
            @endphp

            <section
                class="home-gallery-product-section"
                @if($section->auto_slide)
                    wire:poll.6000ms="nextGallery({{ $section->id }})"
                @endif
            >
                <div class="products-preview-head">
                    <h2>{{ $section->title ?: 'Products' }}</h2>

                    <a href="{{ route('products.index') }}" wire:navigate>
                        View More >
                    </a>
                </div>

                <div class="home-gallery-card">
                    <button type="button" class="gallery-arrow left" wire:click="prevGallery({{ $section->id }})">‹</button>
                    <button type="button" class="gallery-arrow right" wire:click="nextGallery({{ $section->id }})">›</button>

                    <a href="{{ $galleryUrl }}" class="gallery-main-image" wire:navigate>
                        @if($mainImage)
                            <img src="{{ Storage::url($mainImage) }}" alt="{{ $section->title }}">
                        @else
                            <span>CP</span>
                        @endif
                    </a>

                    <div class="gallery-side-images">
                        <a href="{{ $galleryUrl }}" wire:navigate>
                            @if($sideImageOne)
                                <img src="{{ Storage::url($sideImageOne) }}" alt="{{ $section->title }}">
                            @else
                                <span>CP</span>
                            @endif
                        </a>

                        <a href="{{ $galleryUrl }}" wire:navigate>
                            @if($sideImageTwo)
                                <img src="{{ Storage::url($sideImageTwo) }}" alt="{{ $section->title }}">
                            @else
                                <span>CP</span>
                            @endif
                        </a>
                    </div>

                    <div class="gallery-info">
                        <div>
                            <h3>{{ $section->product?->name ?? $section->title }}</h3>

                            @if($section->product)
                                <p>
                                    Rp {{ number_format($section->product->sale_price ?: $section->product->price, 0, ',', '.') }}
                                </p>
                            @else
                                <p>{{ $section->subtitle }}</p>
                            @endif
                        </div>

                        <div class="preview-dots">
                            @foreach($images as $index => $image)
                                <span @class(['active' => ($galleryIndexes[$section->id] ?? 0) === $index])></span>
                            @endforeach
                        </div>

                        <a href="{{ $galleryUrl }}" class="showcase-pill-button" wire:navigate>
                            {{ $section->button_text ?: 'Learn More' }}
                        </a>
                    </div>
                </div>
            </section>
        @endif
    @endforeach
</div>