<?php

use App\Services\CartService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.shop')]
#[Title('Keranjang - Compify')]
class extends Component {
    #[Computed]
    public function items()
    {
        return app(CartService::class)->items();
    }

    #[Computed]
    public function total(): int
    {
        return app(CartService::class)->subtotal();
    }

    public function updateQty(string $cartKey, int $qty): void
    {
        app(CartService::class)->updateQuantity($cartKey, $qty);
    }

    public function remove(string $cartKey): void
    {
        app(CartService::class)->remove($cartKey);
    }

    public function clearCart(): void
    {
        app(CartService::class)->clear();
    }

    public function imageUrl(?string $path): ?string
    {
        return $path ? Storage::url($path) : null;
    }

    public function formatRupiah(int|float|null $value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
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

    @if($errors->has('cart'))
        <div class="flash-success" style="background: rgba(220, 38, 38, .1); color: #b91c1c;">
            {{ $errors->first('cart') }}
        </div>
    @endif

    <div class="cart-layout">
        <div class="cart-list">
            @forelse($this->items as $item)
                @php
                    $image = $this->imageUrl($item['image'] ?? null);
                    $isCombo = $item['type'] === 'combo_package';
                    $detailUrl = $isCombo
                        ? route('event.packages.show', $item['slug'])
                        : route('products.show', $item['product']);
                @endphp

                <div class="cart-item {{ $isCombo ? 'cart-item--combo' : '' }}">
                    <a href="{{ $detailUrl }}" class="cart-item-image" wire:navigate>
                        @if($image)
                            <img src="{{ $image }}" alt="{{ $item['name'] }}">
                        @else
                            <span>{{ strtoupper(substr($item['name'], 0, 2)) }}</span>
                        @endif
                    </a>

                    <div class="cart-item-info">
                        <strong>{{ $item['name'] }}</strong>

                        <span>
                            {{ $item['brand_or_category'] ?? ($isCombo ? 'Paket Bundling' : 'Produk') }}
                        </span>

                        @if(! $item['is_available'])
                            <p style="color: #b91c1c; font-weight: 800;">
                                {{ $item['message'] }}
                            </p>
                        @else
                            @if(($item['discount_amount'] ?? 0) > 0)
                                <small class="cart-old-price">
                                    {{ $this->formatRupiah($item['original_price']) }}
                                </small>
                            @endif

                            <p>
                                {{ $this->formatRupiah($item['unit_price']) }}

                                @if($item['is_event_price'] ?? false)
                                    <em>{{ $item['price_label'] }}</em>
                                @endif
                            </p>
                        @endif

                        @if($isCombo && $item['children']->isNotEmpty())
                            <div class="cart-combo-children">
                                @foreach($item['children'] as $child)
                                    <div>
                                        <span>{{ $child['quantity'] }}x {{ $child['name'] }}</span>
                                        <small>{{ $this->formatRupiah($child['line_total']) }}</small>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <input
                        type="number"
                        min="1"
                        max="{{ max(1, (int) ($item['max_quantity'] ?? 1)) }}"
                        value="{{ $item['quantity'] }}"
                        wire:change="updateQty('{{ $item['key'] }}', $event.target.value)"
                        @disabled(! $item['is_available'])
                    >

                    <b>{{ $this->formatRupiah($item['line_total']) }}</b>

                    <button type="button" wire:click="remove('{{ $item['key'] }}')">
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
                <strong>{{ $this->formatRupiah($this->total) }}</strong>
            </div>

            <div>
                <span>Pengiriman</span>
                <strong>Belum dihitung</strong>
            </div>

            <hr>

            <div>
                <span>Total</span>
                <strong>{{ $this->formatRupiah($this->total) }}</strong>
            </div>

            <a href="{{ route('checkout.index') }}" class="cart-checkout-button" wire:navigate>
                Lanjut ke Pembayaran
            </a>
        </aside>
    </div>
</section>