<?php

use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.shop')]
#[Title('Keranjang - Compify')]
class extends Component {
    #[Computed]
    public function items()
    {
        $cart = session('cart', []);

        return Product::with(['category', 'brand'])
            ->whereIn('id', array_keys($cart))
            ->get()
            ->map(function ($product) use ($cart) {
                $product->cart_qty = $cart[$product->id] ?? 1;
                $product->cart_total = ($product->sale_price ?: $product->price) * $product->cart_qty;

                return $product;
            });
    }

    #[Computed]
    public function total()
    {
        return $this->items->sum('cart_total');
    }

    public function updateQty(int $productId, int $qty): void
    {
        $cart = session('cart', []);

        if ($qty <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = $qty;
        }

        session()->put('cart', $cart);
    }

    public function remove(int $productId): void
    {
        $cart = session('cart', []);
        unset($cart[$productId]);

        session()->put('cart', $cart);
    }

    public function clearCart(): void
    {
        session()->forget('cart');
    }
};
?>

<section class="cart-page">
    <a href="{{ route('products.index') }}" class="product-back-button" wire:navigate>
        ← Lanjut Belanja
    </a>

    <div class="cart-head">
        <div>
            <p>Shopping Cart</p>
            <h1>Keranjang Belanja</h1>
        </div>

        @if($this->items->isNotEmpty())
            <button type="button" wire:click="clearCart">Kosongkan</button>
        @endif
    </div>

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    <div class="cart-layout">
        <div class="cart-list">
            @forelse($this->items as $product)
                <div class="cart-item">
                    <div class="cart-item-image">
                        @if($product->image)
                            <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}">
                        @else
                            <span>{{ strtoupper(substr($product->name, 0, 2)) }}</span>
                        @endif
                    </div>

                    <div class="cart-item-info">
                        <strong>{{ $product->name }}</strong>
                        <span>{{ $product->brand?->name ?? $product->category?->name }}</span>
                        <p>Rp {{ number_format($product->sale_price ?: $product->price, 0, ',', '.') }}</p>
                    </div>

                    <input
                        type="number"
                        min="1"
                        value="{{ $product->cart_qty }}"
                        wire:change="updateQty({{ $product->id }}, $event.target.value)"
                    >

                    <b>Rp {{ number_format($product->cart_total, 0, ',', '.') }}</b>

                    <button type="button" wire:click="remove({{ $product->id }})">
                        Hapus
                    </button>
                </div>
            @empty
                <div class="empty-state">
                    <h3>Keranjang kosong</h3>
                    <p>Tambahkan produk dari halaman detail produk.</p>
                    <a href="{{ route('products.index') }}" class="hero-button dark-button" wire:navigate>
                        Lihat Produk
                    </a>
                </div>
            @endforelse
        </div>

        <aside class="cart-summary">
            <h2>Ringkasan</h2>

            <div>
                <span>Subtotal</span>
                <strong>Rp {{ number_format($this->total, 0, ',', '.') }}</strong>
            </div>

            <div>
                <span>Pengiriman</span>
                <strong>Belum dihitung</strong>
            </div>

            <hr>

            <div>
                <span>Total</span>
                <strong>Rp {{ number_format($this->total, 0, ',', '.') }}</strong>
            </div>

            <a href="{{ route('customer.login') }}" wire:navigate>
                Lanjut Checkout
            </a>
        </aside>
    </div>
</section>