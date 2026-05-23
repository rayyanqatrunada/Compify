<?php

use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.shop')]
#[Title('Wishlist - Compify')]
class extends Component {
    #[Computed]
    public function products()
    {
        return Product::with(['category', 'brand'])
            ->active()
            ->whereIn('id', session('wishlist', []))
            ->latest()
            ->get();
    }
};
?>

<section class="section shop-soft-section">
    <div class="shop-section-head">
        <div>
            <p class="section-kicker">Produk yang kamu simpan</p>
            <h2>Wishlist</h2>
        </div>

        <a href="{{ route('products.index') }}" wire:navigate>Lanjut Belanja ></a>
    </div>

    <div class="product-grid modern-product-grid">
        @forelse($this->products as $product)
            <x-product-card :product="$product" />
        @empty
            <div class="empty-state">
                <h3>Wishlist masih kosong</h3>
                <p>Klik tombol hati di produk untuk menyimpan produk favoritmu.</p>
                <a href="{{ route('products.index') }}" class="hero-button" wire:navigate>
                    Lihat Produk
                </a>
            </div>
        @endforelse
    </div>
</section>