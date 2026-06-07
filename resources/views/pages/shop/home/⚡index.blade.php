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
    public string $slideDirection = 'next'; // 'next' | 'prev'

    public function nextBanner(): void
    {
        $count = $this->banners->count();
        if ($count > 0) {
            $this->slideDirection = 'next';
            $this->bannerIndex = ($this->bannerIndex + 1) % $count;
        }
    }

    public function prevBanner(): void
    {
        $count = $this->banners->count();
        if ($count > 0) {
            $this->slideDirection = 'prev';
            $this->bannerIndex = ($this->bannerIndex - 1 + $count) % $count;
        }
    }

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
        $allBanners = $this->banners;
    @endphp


    <section
        class="hero-slider"
        x-data
        x-ref="slider"
        wire:ignore
    >
        @foreach($allBanners as $index => $b)
            @php
                $bImage = $b->image ? Storage::url($b->image) : null;
                $bVideo = ($b->asset_type === 'video' && $b->video) ? Storage::url($b->video) : null;
            @endphp

            <div
                class="hero-slide"
                data-index="{{ $index }}"
                @if($bImage && !$bVideo)
                    style="background-image: url('{{ $bImage }}')"
                @endif
            >
                @if($bVideo)
                    <div class="hero-video-bg">
                        <div class="hero-video-overlay"></div>
                        <video
                            src="{{ $bVideo }}"
                            @if($bImage) poster="{{ $bImage }}" @endif
                            autoplay muted loop playsinline
                        ></video>
                    </div>
                @endif

                <div class="hero-copy">
                    <p class="hero-eyebrow">Compify Computer Store</p>
                    <h1>{{ $b->title ?? 'Build PC Impianmu di Compify' }}</h1>
                    <p class="hero-support">
                        {{ $b->subtitle ?? 'Temukan motherboard, PSU, RAM, SSD, casing, dan perlengkapan komputer terbaik.' }}
                    </p>
                    <a href="{{ $b->button_url ?? route('products.index') }}" class="hero-button" wire:navigate>
                        {{ $b->button_text ?? 'Belanja Sekarang' }}
                    </a>
                </div>
            </div>
        @endforeach

        @if($allBanners->count() > 1)
            <button type="button" class="hero-arrow prev" id="hero-prev">‹</button>
            <button type="button" class="hero-arrow next" id="hero-next">›</button>

            <div class="hero-dots" id="hero-dots">
                @foreach($allBanners as $index => $b)
                    <button
                        type="button"
                        data-dot="{{ $index }}"
                        aria-label="Banner {{ $index + 1 }}"
                    ></button>
                @endforeach
            </div>
        @endif
    </section>

    <script>
        (function () {
            const DURATION = 620;
            const AUTO_MS  = 20000;

            let autoTimer   = null;
            let initialized = false;

            function destroySlider() {
                clearInterval(autoTimer);
                autoTimer   = null;
                initialized = false;
            }

            function animateCopy(slide) {
                const copy = slide.querySelector('.hero-copy');
                if (!copy) return;

                copy.classList.remove('is-animating');
                void copy.offsetHeight;

                copy.querySelectorAll('*').forEach(el => {
                    el.style.animation = 'none';
                    void el.offsetHeight;
                    el.style.animation = '';
                });

                copy.classList.add('is-animating');
            }

            function initSlider() {
                if (initialized) return;

                const slider = document.querySelector('.hero-slider');
                if (!slider) return;

                const slides = Array.from(slider.querySelectorAll('.hero-slide'));
                if (!slides.length) return;

                // slide tunggal: langsung tampil dan animasi teks
                if (slides.length === 1) {
                    slides[0].classList.add('is-active');
                    setTimeout(() => animateCopy(slides[0]), 100);
                    initialized = true;
                    return;
                }

                const dots    = Array.from(slider.querySelectorAll('[data-dot]'));
                const btnPrev = slider.querySelector('#hero-prev');
                const btnNext = slider.querySelector('#hero-next');

                let current = 0;
                let busy    = false;

                function goTo(next, direction) {
                    if (busy || next === current) return;
                    busy = true;

                    const prev  = current;
                    const enter = direction === 'next' ? 'is-entering-next' : 'is-entering-prev';
                    const leave = direction === 'next' ? 'is-leaving-next'  : 'is-leaving-prev';

                    // reset teks slide berikutnya SEBELUM slide masuk
                    const nextCopy = slides[next].querySelector('.hero-copy');
                    if (nextCopy) {
                        nextCopy.classList.remove('is-animating');
                        void nextCopy.offsetHeight;
                        nextCopy.querySelectorAll('*').forEach(el => {
                            el.style.animation = 'none';
                            void el.offsetHeight;
                            el.style.animation = '';
                        });
                    }

                    slides.forEach(s => s.classList.remove(
                        'is-active', 'is-entering-next', 'is-entering-prev',
                        'is-leaving-next', 'is-leaving-prev'
                    ));

                    slides[prev].classList.add('is-active', leave);
                    slides[next].classList.add(enter);

                    dots.forEach((d, i) => d.classList.toggle('active', i === next));

                    setTimeout(() => {
                        slides.forEach(s => s.classList.remove(
                            'is-active', 'is-entering-next', 'is-entering-prev',
                            'is-leaving-next', 'is-leaving-prev'
                        ));
                        slides[next].classList.add('is-active');
                        animateCopy(slides[next]); // teks baru muncul setelah slide selesai
                        current = next;
                        busy    = false;
                    }, DURATION);
                }

                function next() { goTo((current + 1) % slides.length, 'next'); }
                function prev() { goTo((current - 1 + slides.length) % slides.length, 'prev'); }

                function startAuto() { autoTimer = setInterval(next, AUTO_MS); }
                function resetAuto() { clearInterval(autoTimer); startAuto(); }

                btnNext?.addEventListener('click', () => { resetAuto(); next(); });
                btnPrev?.addEventListener('click', () => { resetAuto(); prev(); });

                dots.forEach((dot, i) => {
                    dot.addEventListener('click', () => {
                        resetAuto();
                        goTo(i, i > current ? 'next' : 'prev');
                    });
                });

                slider.addEventListener('mouseenter', () => clearInterval(autoTimer));
                slider.addEventListener('mouseleave', () => startAuto());

                // tampilkan slide pertama, teks muncul setelah page load selesai
                slides[0].classList.add('is-active');
                startAuto();

                // delay teks slide pertama = sama dengan DURATION
                // supaya konsisten dengan putaran berikutnya
                setTimeout(() => animateCopy(slides[0]), DURATION);

                initialized = true;
            }

            // reset initialized setiap kali Livewire akan navigasi ke halaman baru
            document.addEventListener('livewire:navigating', destroySlider);
            document.addEventListener('DOMContentLoaded', initSlider);
            document.addEventListener('livewire:navigated', initSlider);
        })();
    </script>

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