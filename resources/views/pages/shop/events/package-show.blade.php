<?php

use App\Models\ComboPackage;
use App\Models\EventSetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.shop')]
#[Title('Detail Paket Bundling - Compify')]
class extends Component
{
    public ComboPackage $comboPackage;

    public function mount(ComboPackage $comboPackage): void
    {
        abort_unless($comboPackage->is_active, 404);

        $this->comboPackage = $comboPackage->load([
            'items.product.brand',
            'items.product.category',
        ]);
    }

    public function getEventProperty(): ?EventSetting
    {
        return EventSetting::activeNow();
    }

    public function imageUrl(?string $path): ?string
    {
        return $path ? Storage::url($path) : null;
    }
};
?>

<div class="event-page">
    @if (! $this->event)
        <section class="event-empty">
            <div class="event-empty__box">
                <p>Event</p>
                <h1>Tidak ada event saat ini</h1>
                <span>Paket bundling hanya bisa dilihat saat event berjalan.</span>
                <a href="{{ route('products.index') }}" wire:navigate>Lihat Produk</a>
            </div>
        </section>
    @else
        <section class="event-package-detail-v2">
            <div class="event-package-detail-v2__media">
                @if ($this->imageUrl($comboPackage->image))
                    <img src="{{ $this->imageUrl($comboPackage->image) }}" alt="{{ $comboPackage->name }}">
                @else
                    <div class="event-package-placeholder-v2">
                        {{ strtoupper(substr($comboPackage->name, 0, 2)) }}
                    </div>
                @endif
            </div>

            <div class="event-package-detail-v2__info">
                <a href="{{ route('event.index') }}" class="event-back-link-v2" wire:navigate>
                    ← Kembali ke Event
                </a>

                <span class="event-package-label-v2">Paket Bundling</span>

                <h1>{{ $comboPackage->name }}</h1>

                @if ($comboPackage->subtitle)
                    <p class="event-package-subtitle-v2">{{ $comboPackage->subtitle }}</p>
                @endif

                @if ($comboPackage->description)
                    <div class="event-package-desc-v2">
                        {{ $comboPackage->description }}
                    </div>
                @endif

                <div class="event-package-price-box-v2">
                    <div>
                        <span>Total Harga Barang</span>
                        <del>{{ $comboPackage->formatted_original_total }}</del>
                    </div>

                    <div>
                        <span>{{ $comboPackage->discount_label }}</span>
                        <strong>- {{ $comboPackage->formatted_savings }}</strong>
                    </div>

                    <div class="is-final">
                        <span>Harga Paket</span>
                        <strong>{{ $comboPackage->formatted_package_price }}</strong>
                    </div>
                </div>

                <div class="event-package-actions-v2">
                    <form method="POST" action="{{ route('cart.add.combo', $comboPackage) }}">
                        @csrf

                        <input type="hidden" name="quantity" value="1">

                        <button type="submit">
                            Beli Paket
                        </button>
                    </form>

                    <small>
                        Paket akan masuk ke keranjang sebagai satu item, dengan isi produk tetap ditampilkan.
                    </small>
                </div>
            </div>
        </section>

        <section class="event-section event-package-items-section">
            <div class="event-section-head">
                <h2>Produk Dalam Paket</h2>
                <span>{{ $comboPackage->items->count() }} produk</span>
            </div>

            <div class="event-package-items-v2">
                @foreach ($comboPackage->items as $item)
                    @php
                        $product = $item->product;
                        $image = $this->imageUrl($product?->image);
                    @endphp

                    <article class="event-package-item-card-v2">
                        <a
                            href="{{ $product ? route('products.show', $product) : '#' }}"
                            class="event-package-item-card-v2__image"
                            wire:navigate
                        >
                            @if ($image)
                                <img src="{{ $image }}" alt="{{ $product?->name }}">
                            @else
                                <span>No Image</span>
                            @endif
                        </a>

                        <div class="event-package-item-card-v2__content">
                            <h3>{{ $product?->name ?? 'Produk tidak ditemukan' }}</h3>

                            <p>
                                {{ $product?->brand?->name ?? $product?->category?->name ?? 'Produk Paket' }}
                            </p>

                            <div>
                                <span>Qty: {{ $item->quantity }}</span>
                                <strong>{{ $product?->formatted_final_price }}</strong>
                            </div>

                            <small>
                                Subtotal: {{ 'Rp ' . number_format($item->line_total, 0, ',', '.') }}
                            </small>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>