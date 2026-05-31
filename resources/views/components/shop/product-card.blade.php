@props(['product'])

@php
    $hasDiscount = (bool) $product->has_discount;
    $discountPercent = $product->discount_percent;
    $isEventPrice = (bool) $product->is_event_price;
@endphp

<div class="shop-product-card">
    <a href="{{ route('products.show', $product) }}" class="shop-product-card__image-wrap" wire:navigate>
        @if ($discountPercent)
            <span class="shop-product-card__badge">
                {{ $isEventPrice ? 'Flash Sale ' : '' }}-{{ $discountPercent }}%
            </span>
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

        <a href="{{ route('products.show', $product) }}" class="shop-product-card__title" wire:navigate>
            {{ $product->name }}
        </a>

        <div class="shop-product-card__price">
            @if ($hasDiscount)
                <span class="shop-product-card__price-old">
                    {{ $product->formatted_original_price }}
                </span>

                <span class="shop-product-card__price-new">
                    {{ $product->formatted_final_price }}
                </span>
            @else
                <span class="shop-product-card__price-new">
                    {{ $product->formatted_final_price }}
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
                <button type="submit" @disabled($this->maxPurchasableStock() < 1)>
                    {{ $this->maxPurchasableStock() < 1 ? 'Stok Habis' : 'Tambah ke Keranjang' }}
                </button>
            </form>
        </div>
    </div>
</div>