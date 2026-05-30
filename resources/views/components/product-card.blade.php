@props(['product'])

@php
    $image = $product->image
        ? \Illuminate\Support\Facades\Storage::url($product->image)
        : null;

    $isPromoActive = (bool) ($product->is_promo_active ?? false);
    $discount = $product->discount_percent ?? null;
    $finalPrice = $product->final_price ?? $product->price;

    $isWishlisted = in_array($product->id, session('wishlist', []));
@endphp

<article class="product-card product-card-modern">
    <div class="product-badges">
        @if($product->is_new)
            <span class="badge badge-dark">New</span>
        @endif

        @if($product->is_featured)
            <span class="badge badge-soft">Featured</span>
        @endif

        @if($discount)
            <span class="badge badge-sale">-{{ $discount }}%</span>
        @endif
    </div>

    <form method="POST" action="{{ route('wishlist.toggle', $product) }}" class="wishlist-form">
        @csrf

        <button type="submit" class="wishlist-button {{ $isWishlisted ? 'active' : '' }}" title="Wishlist">
            {{ $isWishlisted ? '♥' : '♡' }}
        </button>
    </form>

    <a href="{{ route('products.show', $product) }}" class="product-image modern-product-image" wire:navigate>
        @if($image)
            <img src="{{ $image }}" alt="{{ $product->name }}">
        @else
            <div class="product-placeholder">
                {{ strtoupper(substr($product->name, 0, 2)) }}
            </div>
        @endif
    </a>

    <div class="product-info">
        <div class="product-brand">
            {{ $product->brand?->name ?? $product->category?->name }}
        </div>

        <a href="{{ route('products.show', $product) }}" wire:navigate>
            <h3 class="product-name">{{ $product->name }}</h3>
        </a>

        <div class="price-row">
            @if($isPromoActive)
                <span class="old-price">
                    Rp {{ number_format($product->price, 0, ',', '.') }}
                </span>

                <span class="sale-price">
                    Rp {{ number_format($finalPrice, 0, ',', '.') }}
                </span>
            @else
                <span>
                    Rp {{ number_format($product->price, 0, ',', '.') }}
                </span>
            @endif
        </div>

        <div class="product-meta">
            <span>{{ $product->stock > 0 ? 'Stok tersedia' : 'Stok habis' }}</span>

            <a href="{{ route('products.show', $product) }}" wire:navigate>
                Detail >
            </a>
        </div>
    </div>
</article>