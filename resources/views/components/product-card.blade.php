@props(['product'])

@php
    $image = $product->image
        ? \Illuminate\Support\Facades\Storage::url($product->image)
        : null;
@endphp

<a href="{{ route('products.show', $product) }}" class="product-card" wire:navigate>
    <div class="product-image">
        @if($image)
            <img src="{{ $image }}" alt="{{ $product->name }}">
        @else
            <div class="product-placeholder">
                {{ strtoupper(substr($product->name, 0, 2)) }}
            </div>
        @endif
    </div>

    <div class="product-brand">
        {{ $product->brand?->name ?? $product->category?->name }}
    </div>

    <h3 class="product-name">{{ $product->name }}</h3>

    <div class="price-row">
        @if($product->sale_price)
            <span class="old-price">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
            <span class="sale-price">Rp {{ number_format($product->sale_price, 0, ',', '.') }}</span>
        @else
            <span>Rp {{ number_format($product->price, 0, ',', '.') }}</span>
        @endif
    </div>
</a>