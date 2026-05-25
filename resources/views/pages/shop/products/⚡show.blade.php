<?php

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.shop')]
#[Title('Detail Produk - Compify')]
class extends Component {
    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product->load(['category', 'brand']);
    }
};
?>

<section class="product-show-page">
    <a href="{{ url()->previous() }}" class="product-back-button">
        ← Kembali
    </a>

    @if(session('success'))
        <div class="flash-success product-flash">
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

    <div class="product-show-layout">
        <div class="product-show-media">
            @if($discount)
                <span class="product-show-badge">-{{ $discount }}%</span>
            @endif

            @if($image)
                <img src="{{ $image }}" alt="{{ $product->name }}">
            @else
                <div class="product-placeholder">
                    {{ strtoupper(substr($product->name, 0, 2)) }}
                </div>
            @endif
        </div>

        <div class="product-show-info">
            <p class="product-show-kicker">
                {{ $product->brand?->name ?? 'Compify Product' }}
            </p>

            <h1>{{ $product->name }}</h1>

            <div class="product-show-meta">
                <span>{{ $product->category?->name ?? 'Tanpa kategori' }}</span>
                <span>{{ $product->stock > 0 ? 'Stok tersedia' : 'Stok habis' }}</span>
                <span>SKU: {{ $product->sku ?? '-' }}</span>
            </div>

            <div class="product-show-price">
                @if($product->sale_price)
                    <small>Rp {{ number_format($product->price, 0, ',', '.') }}</small>
                @endif

                <strong>Rp {{ number_format($finalPrice, 0, ',', '.') }}</strong>
            </div>

            <p class="product-show-short">
                {{ Str::limit($product->description ?: 'Produk pilihan Compify untuk kebutuhan komputer, gaming, kerja, dan upgrade setup.', 170) }}
            </p>

            <div class="product-action-box">
                <form method="POST" action="{{ route('cart.add', $product) }}" class="product-buy-form">
                    @csrf

                    <label>
                        Jumlah
                        <input
                            type="number"
                            name="quantity"
                            min="1"
                            max="{{ max(1, $product->stock) }}"
                            value="1"
                            {{ $product->stock <= 0 ? 'disabled' : '' }}
                        >
                    </label>

                    <button type="submit" {{ $product->stock <= 0 ? 'disabled' : '' }}>
                        Masukkan Keranjang
                    </button>

                    <button
                        type="submit"
                        name="redirect_to_cart"
                        value="1"
                        class="buy-now-button"
                        {{ $product->stock <= 0 ? 'disabled' : '' }}
                    >
                        Beli Sekarang
                    </button>
                </form>

                <form method="POST" action="{{ route('wishlist.toggle', $product) }}">
                    @csrf
                    <button type="submit" class="product-wishlist-button {{ $isWishlisted ? 'active' : '' }}">
                        {{ $isWishlisted ? '♥ Wishlist' : '♡ Tambah Wishlist' }}
                    </button>
                </form>
            </div>

            <div class="product-service-grid">
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

    <div class="product-description-layout">
        <div class="product-description-main">
            <p class="section-kicker">Product Description</p>
            <h2>Deskripsi Lengkap</h2>

            <div class="product-description-text">
                {!! nl2br(e($product->description ?: 'Belum ada deskripsi lengkap untuk produk ini.')) !!}
            </div>
        </div>

        <aside class="product-description-side">
            <h3>Informasi Produk</h3>

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
                <strong>{{ $product->is_active ? 'Aktif' : 'Tidak aktif' }}</strong>
            </div>
        </aside>
    </div>
</section>