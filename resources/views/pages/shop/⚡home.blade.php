<?php

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

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

    <section class="section">
        <div class="section-title">
            <h2>Kategori</h2>
        </div>

        <div class="product-grid">
            @foreach($this->categories as $category)
                <a href="{{ route('categories.show', $category) }}" class="product-card" wire:navigate>
                    <div class="product-image">
                        <div class="product-placeholder">{{ strtoupper(substr($category->name, 0, 2)) }}</div>
                    </div>
                    <h3 class="product-name">{{ $category->name }}</h3>
                </a>
            @endforeach
        </div>
    </section>

    @php
        $featuredWindow = $this->featuredProducts->slice($featuredProductIndex)->values();

        $promoOne = $featuredWindow->get(0) ?? $this->featuredProducts->get(0);
        $promoTwo = $featuredWindow->get(1) ?? $this->featuredProducts->get(1) ?? $promoOne;

        $spotlightMain = $featuredWindow->get(2) ?? $this->featuredProducts->get(2) ?? $promoOne;
        $spotlightSideTop = $featuredWindow->get(3) ?? $this->featuredProducts->get(3) ?? $promoTwo;
        $spotlightSideBottom = $featuredWindow->get(4) ?? $this->featuredProducts->get(4) ?? $promoOne;

        $gridProducts = $this->featuredProducts->slice($featuredProductIndex + 5, 8);

        if ($gridProducts->isEmpty()) {
            $gridProducts = $this->featuredProducts->slice(0, 8);
        }

        $promoOneImage = $promoOne?->image ? Storage::url($promoOne->image) : null;
        $promoTwoImage = $promoTwo?->image ? Storage::url($promoTwo->image) : null;
        $spotlightMainImage = $spotlightMain?->image ? Storage::url($spotlightMain->image) : null;
        $spotlightSideTopImage = $spotlightSideTop?->image ? Storage::url($spotlightSideTop->image) : null;
        $spotlightSideBottomImage = $spotlightSideBottom?->image ? Storage::url($spotlightSideBottom->image) : null;
    @endphp

    @if($promoOne)
    <section class="after-hero-showcase">
        {{-- Preview 1 --}}
        <div class="showcase-split-card reverse-text">
            <div class="showcase-split-copy">
                <h3>{{ $promoOne->name }}</h3>

                <ul class="showcase-points">
                    <li>Kategori: {{ $promoOne->category?->name ?? 'Produk Unggulan' }}</li>
                    <li>Merk: {{ $promoOne->brand?->name ?? 'Compify Choice' }}</li>
                    <li>
                        Harga:
                        Rp {{ number_format($promoOne->sale_price ?: $promoOne->price, 0, ',', '.') }}
                    </li>
                </ul>

                <a href="{{ route('products.show', $promoOne) }}" class="showcase-pill-button" wire:navigate>
                    Learn More
                </a>
            </div>

            <a href="{{ route('products.show', $promoOne) }}" class="showcase-split-image" wire:navigate>
                @if($promoOneImage)
                    <img src="{{ $promoOneImage }}" alt="{{ $promoOne->name }}">
                @else
                    <div class="showcase-image-placeholder">
                        {{ strtoupper(substr($promoOne->name, 0, 2)) }}
                    </div>
                @endif
            </a>
        </div>

        {{-- Preview 2 --}}
        @if($promoTwo)
        <div class="showcase-split-card">
            <a href="{{ route('products.show', $promoTwo) }}" class="showcase-split-image" wire:navigate>
                @if($promoTwoImage)
                    <img src="{{ $promoTwoImage }}" alt="{{ $promoTwo->name }}">
                @else
                    <div class="showcase-image-placeholder">
                        {{ strtoupper(substr($promoTwo->name, 0, 2)) }}
                    </div>
                @endif
            </a>

            <div class="showcase-split-copy">
                <h3>{{ $promoTwo->name }}</h3>

                <p class="showcase-description">
                    {{ \Illuminate\Support\Str::limit($promoTwo->description ?: 'Produk pilihan Compify yang bisa diatur langsung dari admin site dan ditampilkan dari database asli.', 180) }}
                </p>

                <a href="{{ route('products.show', $promoTwo) }}" class="showcase-pill-button" wire:navigate>
                    Learn More
                </a>
            </div>
        </div>
        @endif

        {{-- Products showcase --}}
        @if($spotlightMain)
        <div class="products-preview-section">
            <div class="products-preview-head">
                <h2>Products</h2>
                <a href="{{ route('products.index') }}" wire:navigate>View More ></a>
            </div>

            <div class="products-preview-card">
                <button type="button" class="products-preview-arrow left" wire:click="prevFeaturedProducts">‹</button>
                <button type="button" class="products-preview-arrow right" wire:click="nextFeaturedProducts">›</button>

                <a href="{{ route('products.show', $spotlightMain) }}" class="products-preview-main" wire:navigate>
                    @if($spotlightMainImage)
                        <img src="{{ $spotlightMainImage }}" alt="{{ $spotlightMain->name }}">
                    @else
                        <div class="showcase-image-placeholder">
                            {{ strtoupper(substr($spotlightMain->name, 0, 2)) }}
                        </div>
                    @endif
                </a>

                <div class="products-preview-side">
                    <a href="{{ route('products.show', $spotlightSideTop) }}" class="products-preview-side-item" wire:navigate>
                        @if($spotlightSideTopImage)
                            <img src="{{ $spotlightSideTopImage }}" alt="{{ $spotlightSideTop->name }}">
                        @else
                            <div class="showcase-image-placeholder">
                                {{ strtoupper(substr($spotlightSideTop->name, 0, 2)) }}
                            </div>
                        @endif
                    </a>

                    <a href="{{ route('products.show', $spotlightSideBottom) }}" class="products-preview-side-item" wire:navigate>
                        @if($spotlightSideBottomImage)
                            <img src="{{ $spotlightSideBottomImage }}" alt="{{ $spotlightSideBottom->name }}">
                        @else
                            <div class="showcase-image-placeholder">
                                {{ strtoupper(substr($spotlightSideBottom->name, 0, 2)) }}
                            </div>
                        @endif
                    </a>
                </div>

                <div class="products-preview-info">
                    <div>
                        <h3>{{ $spotlightMain->name }}</h3>
                        <p>
                            Rp {{ number_format($spotlightMain->sale_price ?: $spotlightMain->price, 0, ',', '.') }}
                        </p>
                    </div>

                    <div class="preview-dots">
                        <span class="active"></span>
                        <span></span>
                        <span></span>
                    </div>

                    <a href="{{ route('products.show', $spotlightMain) }}" class="showcase-pill-button" wire:navigate>
                        Learn More
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- Grid produk --}}
        <div class="showcase-grid-products">
            @foreach($gridProducts as $product)
                @php
                    $image = $product->image ? Storage::url($product->image) : null;
                    $finalPrice = $product->sale_price ?: $product->price;
                @endphp

                <article class="showcase-grid-card">
                    <a href="{{ route('products.show', $product) }}" class="showcase-grid-image" wire:navigate>
                        @if($image)
                            <img src="{{ $image }}" alt="{{ $product->name }}">
                        @else
                            <div class="showcase-image-placeholder">
                                {{ strtoupper(substr($product->name, 0, 2)) }}
                            </div>
                        @endif
                    </a>

                    <div class="showcase-grid-copy">
                        <small>{{ $product->name }}</small>
                        <span>{{ $product->category?->name ?? 'Produk' }}</span>
                        <strong>Rp {{ number_format($finalPrice, 0, ',', '.') }}</strong>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
    @endif
</div>