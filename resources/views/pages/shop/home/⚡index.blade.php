<?php

use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Services\ProductPricingService;

new
#[Layout('components.layouts.shop')]
#[Title('Compify - Toko Perlengkapan Komputer')]
class extends Component {
    public int $bannerIndex = 0;

    public array $sectionIndexes = [];
    public int $activeGalleryIndex = 0;

    #[Computed]
    public function banners()
    {
        return Banner::active()
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function currentBanner()
    {
        return $this->banners->get($this->bannerIndex) ?? $this->banners->first();
    }

    #[Computed]
    public function categories()
    {
        return Category::active()
            ->whereNull('parent_id')
            ->withCount('children')
            ->orderBy('sort_order')
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function homeSections()
    {
        return HomeSection::with(['category.children', 'product'])
            ->active()
            ->orderBy('sort_order')
            ->get();
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

        $products = $query
            ->skip($index)
            ->take(4)
            ->get();

        app(ProductPricingService::class)->preload($products);

        return $products;
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

    public function gallerySections()
    {
        return $this->homeSections
            ->where('section_type', 'gallery')
            ->values();
    }

    public function activeGallerySection(): ?HomeSection
    {
        $galleries = $this->gallerySections();

        return $galleries->get($this->activeGalleryIndex) ?? $galleries->first();
    }

    public function galleryImages(HomeSection $section): array
    {
        return collect([
            $section->image,
            $section->image_2,
            $section->image_3,
        ])->filter()->values()->all();
    }

    public function nextGalleryProduct(): void
    {
        $count = $this->gallerySections()->count();

        if ($count <= 1) {
            return;
        }

        $this->activeGalleryIndex = ($this->activeGalleryIndex + 1) % $count;
    }

    public function prevGalleryProduct(): void
    {
        $count = $this->gallerySections()->count();

        if ($count <= 1) {
            return;
        }

        $this->activeGalleryIndex = ($this->activeGalleryIndex - 1 + $count) % $count;
    }
};
?>

<div class="home-page">
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
                class="hero-slide hero-slide-animated"
                @if($heroImage)
                    style="background-image: linear-gradient(90deg, rgba(0,0,0,.82), rgba(0,0,0,.42), rgba(0,0,0,.12)), url('{{ $heroImage }}')"
                @endif
                wire:key="banner-{{ $banner?->id ?? 'default' }}-{{ $bannerIndex }}"
            >
            <div class="hero-copy hero-copy-fade" wire:key="hero-copy-{{ $banner?->id ?? 'default' }}-{{ $bannerIndex }}">
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
                <button type="button" class="hero-arrow prev" wire:click="prevBanner">‹</button>
                <button type="button" class="hero-arrow next" wire:click="nextBanner">›</button>

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
            @endif
        </div>
    </section>

    @php
        /*
        |--------------------------------------------------------------------------
        | Fixed Homepage Layout
        |--------------------------------------------------------------------------
        | Urutan homepage dikunci di sini.
        | Data tetap diambil dari admin Home Sections.
        */

        $productSections = $this->homeSections
            ->where('section_type', 'category_products')
            ->values();

        $storySections = $this->homeSections
            ->where('section_type', 'story')
            ->values();

        $fullBannerSection = $storySections
            ->first(fn ($item) => $item->display_style === 'full_banner')
            ?? $storySections->first();

        $splitSections = $storySections
            ->filter(fn ($item) => $item->id !== $fullBannerSection?->id && $item->display_style !== 'full_banner')
            ->values();

        $gallerySection = $this->activeGallerySection();

        $homeLayoutSlots = [
            ['type' => 'products', 'index' => 0],
            ['type' => 'full_banner'],
            ['type' => 'products', 'index' => 1],
            ['type' => 'split', 'index' => 0],
            ['type' => 'products', 'index' => 2],
            ['type' => 'split', 'index' => 1],
            ['type' => 'products', 'index' => 3],
            ['type' => 'gallery'],
            ['type' => 'products', 'index' => 4],
            ['type' => 'products', 'index' => 5],
        ];
    @endphp

    @foreach($homeLayoutSlots as $slot)
        @php
            $slotType = $slot['type'];

            $section = match ($slotType) {
                'products' => $productSections->get($slot['index']),
                'full_banner' => $fullBannerSection,
                'split' => $splitSections->get($slot['index']),
                'gallery' => $gallerySection,
                default => null,
            };
        @endphp

        @if(! $section)
            @continue
        @endif

        @if($slotType === 'products')
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

        @if($slotType === 'full_banner' || $slotType === 'split')
            @php
                $isFullStory = $slotType === 'full_banner';
                $storyImage = $section->image ? Storage::url($section->image) : null;
                $buttonUrl = $section->button_url ?: route('products.index');
            @endphp

            <section class="home-story-section {{ $section->image_position === 'left' ? 'image-left' : 'image-right' }} {{ $isFullStory ? 'story-hero-full' : '' }}">
                <div class="home-story-copy">
                    @if($section->subtitle)
                        <p>{{ $section->subtitle }}</p>
                    @endif

                    <h2>{{ $section->title ?: 'Compify Preview' }}</h2>

                    <div class="home-story-description">
                        {!! nl2br(e($section->description ?: 'Tambahkan deskripsi section dari admin.')) !!}
                    </div>

                    <a href="{{ $buttonUrl }}" wire:navigate>
                        {{ $section->button_text ?: 'Learn More' }}
                    </a>
                </div>

                <div class="home-story-image">
                    @if($storyImage)
                        <img src="{{ $storyImage }}" alt="{{ $section->title ?: 'Compify Preview' }}">
                    @else
                        <div class="home-custom-image-empty">
                            <span>NO IMAGE</span>
                            <small>Upload gambar dari admin</small>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if($slotType === 'gallery')
            @php
                $images = $this->galleryImages($section);

                $mainImage = $images[0] ?? null;
                $sideImageOne = $images[1] ?? null;
                $sideImageTwo = $images[2] ?? null;

                $galleryUrl = $section->button_url ?: route('products.index');
            @endphp

            <section
                class="home-gallery-product-section"
                @if($section->auto_slide)
                    wire:poll.6000ms="nextGalleryProduct"
                @endif
            >
                <div class="products-preview-head">
                    <h2>{{ $section->title ?: 'Products Preview' }}</h2>

                    <a href="{{ route('products.index') }}" wire:navigate>
                        View More >
                    </a>
                </div>

                <div
                    class="home-gallery-card gallery-slide-animated"
                    wire:key="gallery-product-{{ $section->id }}-{{ $activeGalleryIndex }}"
                >
                    @if(count($images) > 1)
                        <button type="button" class="gallery-arrow left" wire:click="prevGalleryProduct">‹</button>
                        <button type="button" class="gallery-arrow right" wire:click="nextGalleryProduct">›</button>
                    @endif

                    <a href="{{ $galleryUrl }}" class="gallery-main-image" wire:navigate>
                        @if($mainImage)
                            <img src="{{ Storage::url($mainImage) }}" alt="{{ $section->title ?: 'Gallery Image' }}">
                        @else
                            <div class="home-custom-image-empty">
                                <span>NO IMAGE</span>
                                <small>Upload gambar 1 dari admin</small>
                            </div>
                        @endif
                    </a>

                    <div class="gallery-side-images">
                        <a href="{{ $galleryUrl }}" wire:navigate>
                            @if($sideImageOne)
                                <img src="{{ Storage::url($sideImageOne) }}" alt="{{ $section->title ?: 'Gallery Image 2' }}">
                            @else
                                <div class="home-custom-image-empty">
                                    <span>NO IMAGE</span>
                                    <small>Upload gambar 2</small>
                                </div>
                            @endif
                        </a>

                        <a href="{{ $galleryUrl }}" wire:navigate>
                            @if($sideImageTwo)
                                <img src="{{ Storage::url($sideImageTwo) }}" alt="{{ $section->title ?: 'Gallery Image 3' }}">
                            @else
                                <div class="home-custom-image-empty">
                                    <span>NO IMAGE</span>
                                    <small>Upload gambar 3</small>
                                </div>
                            @endif
                        </a>
                    </div>

                    <div class="gallery-info">
                        <div>
                            <h3>{{ $section->title ?: 'Custom Gallery' }}</h3>
                            <p>{{ $section->subtitle ?: 'Custom homepage gallery' }}</p>
                        </div>

                        <div class="preview-dots">
                            @foreach($this->gallerySections() as $index => $galleryItem)
                                <button
                                    type="button"
                                    wire:click="$set('activeGalleryIndex', {{ $index }})"
                                    @class(['active' => $activeGalleryIndex === $index])
                                    aria-label="Gallery {{ $index + 1 }}"
                                ></button>
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