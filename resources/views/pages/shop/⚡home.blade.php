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
    public function latestProducts()
    {
        return Product::with(['category', 'brand'])
            ->active()
            ->latest()
            ->limit(4)
            ->get();
    }

    #[Computed]
    public function featuredProducts()
    {
        return Product::with(['category', 'brand'])
            ->active()
            ->where('is_featured', true)
            ->latest()
            ->limit(4)
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

    <section class="section">
        <div class="section-title">
            <h2>Produk Terbaru</h2>
        </div>

        <div class="product-grid">
            @foreach($this->latestProducts as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
    </section>

    <section class="section">
        <div class="section-title">
            <h2>Produk Pilihan</h2>
        </div>

        <div class="product-grid">
            @foreach($this->featuredProducts as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
    </section>
</div>