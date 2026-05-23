<?php

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.shop')]
class extends Component {
    public Product $product;

    public function title(): string
    {
        return $this->product->name . ' - Compify';
    }
};
?>

@php
    $image = $product->image ? Storage::url($product->image) : null;
@endphp

<section class="product-detail">
    <div class="detail-image">
        @if($image)
            <img src="{{ $image }}" alt="{{ $product->name }}">
        @else
            <div class="product-placeholder">{{ strtoupper(substr($product->name, 0, 2)) }}</div>
        @endif
    </div>

    <div>
        <p class="product-brand">{{ $product->brand?->name }} / {{ $product->category?->name }}</p>
        <h1 style="font-size: 42px; margin: 0 0 16px;">{{ $product->name }}</h1>

        <div class="price-row" style="justify-content: flex-start; font-size: 26px; margin-bottom: 22px;">
            @if($product->sale_price)
                <span class="old-price">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                <span class="sale-price">Rp {{ number_format($product->sale_price, 0, ',', '.') }}</span>
            @else
                <span>Rp {{ number_format($product->price, 0, ',', '.') }}</span>
            @endif
        </div>

        <p>Stok: {{ $product->stock }}</p>

        <p style="line-height: 1.8;">
            {{ $product->description }}
        </p>

        <a href="{{ route('products.index') }}" class="primary-btn" style="background: black; color: white;" wire:navigate>
            Kembali ke Produk
        </a>
    </div>
</section>