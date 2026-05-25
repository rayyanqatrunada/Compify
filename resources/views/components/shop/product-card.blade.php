@props(['product'])

@php
    $finalPrice = $product->sale_price && $product->sale_price > 0
        ? $product->sale_price
        : $product->price;

    $hasDiscount = $product->sale_price && $product->sale_price < $product->price;

    $discountPercent = $hasDiscount
        ? round((($product->price - $product->sale_price) / $product->price) * 100)
        : 0;
@endphp

<div class="shop-product-card">
    <a href="{{ route('products.show', $product->slug) }}" class="shop-product-card__image-wrap" wire:navigate>
        @if ($hasDiscount)
            <span class="shop-product-card__badge">-{{ $discountPercent }}%</span>
        @endif

        <img
            src="{{ $product->image ? asset('storage/' . $product->image) : asset('images/placeholder-product.png') }}"
            alt="{{ $product->name }}"
            class="shop-product-card__image"
        >
    </a>

    <div class="shop-product-card__content">
        <div class="shop-product-card__brand">
            {{ $product->brand->name ?? 'Brand' }}
        </div>

        <a href="{{ route('products.show', $product->slug) }}" class="shop-product-card__title" wire:navigate>
            {{ $product->name }}
        </a>

        <div class="shop-product-card__price">
            @if ($hasDiscount)
                <span class="shop-product-card__price-old">
                    Rp {{ number_format($product->price, 0, ',', '.') }}
                </span>
                <span class="shop-product-card__price-new">
                    Rp {{ number_format($product->sale_price, 0, ',', '.') }}
                </span>
            @else
                <span class="shop-product-card__price-new">
                    Rp {{ number_format($finalPrice, 0, ',', '.') }}
                </span>
            @endif
        </div>

        <div class="shop-product-card__actions">
            <form method="POST" action="{{ route('wishlist.toggle', $product) }}">
                @csrf
                <button type="submit" class="shop-product-card__icon-btn" title="Wishlist">
                    ♡
                </button>
            </form>

            <form method="POST" action="{{ route('cart.add', $product) }}" class="shop-product-card__cart-form">
                @csrf
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="shop-product-card__cart-btn">
                    Tambah ke Keranjang
                </button>
            </form>
        </div>
    </div>
</div>