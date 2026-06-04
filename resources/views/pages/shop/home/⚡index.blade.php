<?php

use App\Models\Banner;
use App\Models\HomeCategoryGridItem;
use App\Models\HomeCategoryGridSetting;
use App\Models\HomeLayoutGroup;
use App\Models\HomeLayoutSlot;
use App\Models\HomeSection;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\ProductPricingService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.shop')]
#[Title('Compify - Toko Perlengkapan Komputer')]
class extends Component {
    public int $bannerIndex = 0;

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
    public function categoryGridSetting()
    {
        return HomeCategoryGridSetting::current();
    }

    #[Computed]
    public function categoryGridItems()
    {
        return HomeCategoryGridItem::query()
            ->with('category')
            ->active()
            ->whereHas('category', fn ($query) => $query->active())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    #[Computed]
    public function homeLayoutSlots()
    {
        return HomeLayoutGroup::current()
            ->slots()
            ->with([
                'category.children',
                'homeSection',
            ])
            ->where('is_active', true)
            ->orderBy('slot_number')
            ->get()
            ->filter(function (HomeLayoutSlot $slot) {
                if ($slot->slot_type === HomeLayoutSlot::TYPE_PRODUCT_DISPLAY) {
                    $source = $slot->product_source ?? HomeLayoutSlot::SOURCE_CATEGORY;

                    if ($source === HomeLayoutSlot::SOURCE_CATEGORY) {
                        return $slot->category && $slot->category->is_active;
                    }

                    return in_array($source, [
                        HomeLayoutSlot::SOURCE_BEST_SELLER,
                        HomeLayoutSlot::SOURCE_LATEST,
                    ], true);
                }

                if (in_array($slot->slot_type, [
                    HomeLayoutSlot::TYPE_FULL_BANNER,
                    HomeLayoutSlot::TYPE_SPLIT_BANNER,
                    HomeLayoutSlot::TYPE_GALLERY,
                ], true)) {
                    return $slot->homeSection && $slot->homeSection->is_active;
                }

                return false;
            })
            ->values();
    }

    #[Computed]
    public function layoutBlocks(): array
    {
        $blocks = [];
        $productBuffer = [];
        $lastProductKey = null;

        foreach ($this->homeLayoutSlots as $slot) {
            if ($slot->slot_type === HomeLayoutSlot::TYPE_PRODUCT_DISPLAY) {
                $currentProductKey = $this->productSlotGroupKey($slot);

                if ($productBuffer !== [] && $lastProductKey !== $currentProductKey) {
                    $blocks[] = [
                        'type' => 'product_group',
                        'slots' => $productBuffer,
                    ];

                    $productBuffer = [];
                }

                $productBuffer[] = $slot;
                $lastProductKey = $currentProductKey;

                continue;
            }

            if ($productBuffer !== []) {
                $blocks[] = [
                    'type' => 'product_group',
                    'slots' => $productBuffer,
                ];

                $productBuffer = [];
                $lastProductKey = null;
            }

            $blocks[] = [
                'type' => $slot->slot_type,
                'slot' => $slot,
            ];
        }

        if ($productBuffer !== []) {
            $blocks[] = [
                'type' => 'product_group',
                'slots' => $productBuffer,
            ];
        }

        return $blocks;
    }

    public function productSlotGroupKey(HomeLayoutSlot $slot): string
    {
        $source = $slot->product_source ?? HomeLayoutSlot::SOURCE_CATEGORY;

        if ($source === HomeLayoutSlot::SOURCE_CATEGORY) {
            return 'category:' . ($slot->category_id ?: 'none');
        }

        return 'source:' . $source;
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

    public function productsForLayoutSlot(HomeLayoutSlot $slot, int $offset = 0)
    {
        $source = $slot->product_source ?? HomeLayoutSlot::SOURCE_CATEGORY;

        if ($source === HomeLayoutSlot::SOURCE_LATEST) {
            $products = Product::with(['category', 'brand'])
                ->active()
                ->latest()
                ->skip($offset)
                ->take(4)
                ->get();

            app(ProductPricingService::class)->preload($products);

            return $products;
        }

        if ($source === HomeLayoutSlot::SOURCE_BEST_SELLER) {
            $bestSellerIds = OrderItem::query()
                ->whereNotNull('product_id')
                ->select('product_id')
                ->selectRaw('SUM(quantity) as total_sold')
                ->groupBy('product_id')
                ->orderByDesc('total_sold')
                ->limit(100)
                ->pluck('product_id')
                ->values();

            if ($bestSellerIds->isEmpty()) {
                $products = Product::with(['category', 'brand'])
                    ->active()
                    ->latest()
                    ->skip($offset)
                    ->take(4)
                    ->get();

                app(ProductPricingService::class)->preload($products);

                return $products;
            }

            $ids = $bestSellerIds->all();

            $products = Product::with(['category', 'brand'])
                ->active()
                ->whereIn('id', $ids)
                ->get()
                ->sortBy(fn ($product) => array_search($product->id, $ids, true))
                ->values()
                ->skip($offset)
                ->take(4)
                ->values();

            app(ProductPricingService::class)->preload($products);

            return $products;
        }

        $category = $slot->category;

        if (! $category) {
            return collect();
        }

        $categoryIds = collect([$category->id]);

        $categoryIds = $categoryIds->merge(
            $category->children()
                ->active()
                ->pluck('id')
        );

        $products = Product::with(['category', 'brand'])
            ->active()
            ->whereIn('category_id', $categoryIds->unique()->values())
            ->orderBy('sort_order')
            ->latest()
            ->skip($offset)
            ->take(4)
            ->get();

        app(ProductPricingService::class)->preload($products);

        return $products;
    }

    public function productGroupTitle(HomeLayoutSlot $slot): string
    {
        $source = $slot->product_source ?? HomeLayoutSlot::SOURCE_CATEGORY;

        $autoTitle = match ($source) {
            HomeLayoutSlot::SOURCE_BEST_SELLER => 'Best Seller',
            HomeLayoutSlot::SOURCE_LATEST => 'Latest Product',
            default => $slot->category?->name ?: 'Display Produk',
        };

        return $slot->title ?: $autoTitle;
    }

    public function productGroupSubtitle(HomeLayoutSlot $slot): string
    {
        $source = $slot->product_source ?? HomeLayoutSlot::SOURCE_CATEGORY;

        $autoSubtitle = match ($source) {
            HomeLayoutSlot::SOURCE_BEST_SELLER => 'Produk paling banyak dibeli',
            HomeLayoutSlot::SOURCE_LATEST => 'Produk terbaru dari Compify',
            default => 'Display Produk',
        };

        return $slot->subtitle ?: $autoSubtitle;
    }

    public function galleryImages(HomeSection $section): array
    {
        return collect([
            $section->image,
            $section->image_2,
            $section->image_3,
        ])->filter()->values()->all();
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

    @if($this->categoryGridSetting->is_active && $this->categoryGridItems->isNotEmpty())
        <section
            class="home-category-grid-section"
            style="
                --category-grid-desktop: {{ $this->categoryGridSetting->columns_desktop }};
                --category-grid-tablet: {{ $this->categoryGridSetting->columns_tablet }};
                --category-grid-mobile: {{ $this->categoryGridSetting->columns_mobile }};
            "
        >
            <div class="home-category-grid-head">
                <div>
                    <h2>{{ $this->categoryGridSetting->title }}</h2>

                    @if($this->categoryGridSetting->subtitle)
                        <p>{{ $this->categoryGridSetting->subtitle }}</p>
                    @endif
                </div>
            </div>

            <div class="home-category-grid">
                @foreach($this->categoryGridItems as $item)
                    @php
                        $category = $item->category;

                        $image = $item->image
                            ? Storage::url($item->image)
                            : ($category?->image ? Storage::url($category->image) : asset('images/placeholder-product.png'));
                    @endphp

                    <a href="{{ route('categories.show', $category) }}" class="home-category-grid-card" wire:navigate>
                        <span class="home-category-grid-card__image">
                            <img src="{{ $image }}" alt="{{ $item->display_name }}">
                        </span>

                        <strong>{{ $item->display_name }}</strong>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @foreach($this->layoutBlocks as $block)
        @if($block['type'] === 'product_group')
            @php
                $productSlots = collect($block['slots']);
                $firstSlot = $productSlots->first();
                $firstCategory = $firstSlot?->category;
            @endphp

            <section class="home-category-products-section">
                <div class="home-display-head">
                    <div>
                        <p>{{ $firstSlot ? $this->productGroupSubtitle($firstSlot) : 'Display Produk' }}</p>
                        <h2>{{ $firstSlot ? $this->productGroupTitle($firstSlot) : 'Display Produk' }}</h2>
                    </div>

                    <div class="home-display-actions">
                        @if($firstCategory && ($firstSlot?->product_source ?? 'category') === 'category')
                            <a href="{{ route('categories.show', $firstCategory) }}" wire:navigate>
                                Lihat Semua >
                            </a>
                        @else
                            <a href="{{ route('products.index') }}" wire:navigate>
                                Lihat Semua >
                            </a>
                        @endif
                    </div>
                </div>

                <div class="product-grid modern-product-grid">
                    @foreach($productSlots->values() as $slotIndex => $productSlot)
                        @php
                            $offset = $slotIndex * 4;
                        @endphp

                        @foreach($this->productsForLayoutSlot($productSlot, $offset) as $product)
                            <x-product-card :product="$product" />
                        @endforeach
                    @endforeach
                </div>
            </section>
        @endif

        @if(in_array($block['type'], ['full_banner', 'split_banner'], true))
            @php
                $slot = $block['slot'];
                $section = $slot->homeSection;

                $isFullStory = $block['type'] === 'full_banner';
                $storyImage = $section?->image ? Storage::url($section->image) : null;
                $buttonUrl = $section?->button_url ?: route('products.index');
            @endphp

            @if($section)
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
        @endif

        @if($block['type'] === 'gallery')
            @php
                $slot = $block['slot'];
                $section = $slot->homeSection;

                $images = $section ? $this->galleryImages($section) : [];

                $mainImage = $images[0] ?? null;
                $sideImageOne = $images[1] ?? null;
                $sideImageTwo = $images[2] ?? null;

                $galleryUrl = $section?->button_url ?: route('products.index');
            @endphp

            @if($section)
                <section class="home-gallery-product-section">
                    <div class="products-preview-head">
                        <h2>{{ $section->title ?: 'Products Preview' }}</h2>

                        <a href="{{ $galleryUrl }}" wire:navigate>
                            View More >
                        </a>
                    </div>

                    <div class="home-gallery-card gallery-slide-animated">
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

                            <a href="{{ $galleryUrl }}" class="showcase-pill-button" wire:navigate>
                                {{ $section->button_text ?: 'Learn More' }}
                            </a>
                        </div>
                    </div>
                </section>
            @endif
        @endif
    @endforeach
</div>