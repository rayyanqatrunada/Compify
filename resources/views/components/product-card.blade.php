@props(['product'])

@php
    $image = $product->image
        ? \Illuminate\Support\Facades\Storage::url($product->image)
        : null;

    $hasDiscount = (bool) $product->has_discount;
    $discount = $product->discount_percent;
    $isEventPrice = (bool) $product->is_event_price;

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

        @if($isEventPrice)
            <span class="badge badge-sale">Flash Sale</span>
        @endif

        @if($discount)
            <span class="badge badge-sale">-{{ $discount }}%</span>
        @endif
    </div>

    <form
        method="POST"
        action="{{ route('wishlist.toggle', $product) }}"
        class="wishlist-form js-wishlist-form"
    >
        @csrf

        <button
            type="submit"
            class="wishlist-button js-wishlist-button {{ $isWishlisted ? 'active' : '' }}"
            title="Wishlist"
            aria-label="Toggle wishlist"
        >
            <span class="js-wishlist-icon">{{ $isWishlisted ? '♥' : '♡' }}</span>
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
            @if($hasDiscount)
                <span class="old-price">
                    {{ $product->formatted_original_price }}
                </span>

                <span class="sale-price">
                    {{ $product->formatted_final_price }}
                </span>
            @else
                <span>
                    {{ $product->formatted_final_price }}
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