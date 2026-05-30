<?php

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.shop')]
#[Title('Detail Produk - Compify')]
class extends Component {
    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product->load(['category', 'brand']);
    }
};
?>

<section class="product-detail-page">
    @if(session('success'))
        <div class="flash-success product-detail-flash">
            {{ session('success') }}
        </div>
    @endif

    @php
        $image = $product->image ? Storage::url($product->image) : null;

        $finalPrice = $product->sale_price ?: $product->price;

        $discount = $product->sale_price && $product->price > 0
            ? round((($product->price - $product->sale_price) / $product->price) * 100)
            : null;

        $isWishlisted = in_array($product->id, session('wishlist', []));
    @endphp

    <div class="product-detail-container">
        @php
            $previousUrl = url()->previous();
            $backUrl = $previousUrl !== url()->current()
                ? $previousUrl
                : route('products.index');
        @endphp

        <a href="{{ $backUrl }}" class="product-detail-back" wire:navigate>
            <span>←</span>
            Kembali
        </a>

        <div class="product-detail-main">
            <div class="product-detail-media-card">
                @if($discount)
                    <span class="product-detail-discount">-{{ $discount }}%</span>
                @endif

                @if($image)
                    <img src="{{ $image }}" alt="{{ $product->name }}">
                @else
                    <div class="product-detail-placeholder">
                        {{ strtoupper(substr($product->name, 0, 2)) }}
                    </div>
                @endif
            </div>

            <div class="product-detail-info-card">
                <p class="product-detail-brand">
                    {{ $product->brand?->name ?? 'Compify Product' }}
                </p>

                <h1>{{ $product->name }}</h1>

                <div class="product-detail-meta">
                    <span>{{ $product->category?->name ?? 'Tanpa kategori' }}</span>
                    <span>{{ $product->stock > 0 ? 'Stok Tersedia' : 'Stok Habis' }}</span>
                    <span>SKU: {{ $product->sku ?? '-' }}</span>
                </div>

                <div class="product-detail-price">
                    @if($product->sale_price)
                        <small>Rp {{ number_format($product->price, 0, ',', '.') }}</small>
                    @endif

                    <strong>Rp {{ number_format($finalPrice, 0, ',', '.') }}</strong>
                </div>

                <p class="product-detail-short-desc">
                    {{ Str::limit($product->description ?: 'Produk pilihan Compify untuk kebutuhan komputer, gaming, kerja, dan upgrade setup.', 150) }}
                </p>

                <div class="product-detail-actions">
                    <form method="POST" action="{{ route('cart.add', $product) }}" class="product-detail-cart-form">
                        @csrf

                        <label class="product-detail-qty">
                            <span>Jumlah</span>

                            <input
                                type="number"
                                name="quantity"
                                min="1"
                                max="{{ max(1, $product->stock) }}"
                                value="1"
                                {{ $product->stock <= 0 ? 'disabled' : '' }}
                            >
                        </label>

                        <div class="product-detail-button-row">
                            <button
                                type="submit"
                                name="redirect_to_cart"
                                value="1"
                                class="product-detail-buy"
                                {{ $product->stock <= 0 ? 'disabled' : '' }}
                            >
                                Beli Sekarang
                            </button>

                            <button
                                type="submit"
                                class="product-detail-cart"
                                {{ $product->stock <= 0 ? 'disabled' : '' }}
                            >
                                Masukkan Keranjang
                            </button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('wishlist.toggle', $product) }}">
                        @csrf

                        <button type="submit" class="product-detail-wishlist {{ $isWishlisted ? 'active' : '' }}">
                            {{ $isWishlisted ? 'Wishlist Ditambahkan' : 'Tambah Wishlist' }}
                        </button>
                    </form>
                </div>

                <div class="product-detail-service-grid">
                    <div>
                        <strong>Garansi Produk</strong>
                        <span>Garansi sesuai ketentuan produk dan brand.</span>
                    </div>

                    <div>
                        <strong>Pengiriman Aman</strong>
                        <span>Produk dikemas rapi untuk komponen komputer.</span>
                    </div>

                    <div>
                        <strong>Support</strong>
                        <span>Bisa konsultasi kebutuhan build dan upgrade.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="product-detail-bottom">
            <div class="product-detail-description-card">
                <p>Product Description</p>
                <h2>Deskripsi Lengkap</h2>

                <div>
                    {!! nl2br(e($product->description ?: 'Belum ada deskripsi lengkap untuk produk ini.')) !!}
                </div>
            </div>

            <aside class="product-detail-side-card">
                <h3>Informasi Produk</h3>

                <div class="product-detail-info-list">
                    <div>
                        <span>Kategori</span>
                        <strong>{{ $product->category?->name ?? '-' }}</strong>
                    </div>

                    <div>
                        <span>Brand</span>
                        <strong>{{ $product->brand?->name ?? '-' }}</strong>
                    </div>

                    <div>
                        <span>Stok</span>
                        <strong>{{ $product->stock }}</strong>
                    </div>

                    <div>
                        <span>Status</span>
                        <strong>{{ $product->is_active ? 'Aktif' : 'Tidak Aktif' }}</strong>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>