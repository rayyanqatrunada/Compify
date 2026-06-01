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

    public function formatRupiah(int|float|null $value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }
};
?>

<div class="event-page event-package-page-v2">
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
        <main class="combo-detail-shell">
            <section class="combo-detail-hero">
                <div class="combo-detail-media-card">
                    <div class="combo-detail-media-inner">
                        @if ($this->imageUrl($comboPackage->image))
                            <img src="{{ $this->imageUrl($comboPackage->image) }}" alt="{{ $comboPackage->name }}">
                        @else
                            <div class="combo-detail-placeholder">
                                {{ strtoupper(substr($comboPackage->name, 0, 2)) }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="combo-detail-info-card">
                    <a href="{{ route('event.index') }}" class="combo-detail-back" wire:navigate>
                        ← Kembali ke event
                    </a>

                    <h1>{{ $comboPackage->name }}</h1>

                    <div class="combo-detail-badges">
                        <span>Paket bundling</span>
                        <span>Stok terbatas</span>
                    </div>

                    @if ($comboPackage->subtitle)
                        <p class="combo-detail-subtitle">{{ $comboPackage->subtitle }}</p>
                    @endif

                    @if ($comboPackage->description)
                        <p class="combo-detail-desc">{{ $comboPackage->description }}</p>
                    @endif

                    <div class="combo-detail-price-box">
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

                    <div class="combo-detail-action">
                        <small>
                            Paket akan masuk ke keranjang sebagai satu item, dengan isi produk tetap ditampilkan.
                        </small>

                        <form method="POST" action="{{ route('cart.add.combo', $comboPackage) }}">
                            @csrf

                            <input type="hidden" name="quantity" value="1">

                            <button type="submit">
                                Beli Sekarang
                            </button>
                        </form>
                    </div>
                </div>
            </section>

            <section class="combo-detail-items-card">
                <div class="combo-detail-items-head">
                    <h2>Produk dalam paket</h2>
                    <span>{{ $comboPackage->items->count() }} Produk</span>
                </div>

                <div class="combo-detail-items-grid">
                    @foreach ($comboPackage->items as $item)
                        @php
                            $product = $item->product;
                            $image = $this->imageUrl($product?->image);
                        @endphp

                        <article class="combo-detail-item">
                            <a
                                href="{{ $product ? route('products.show', $product) : '#' }}"
                                class="combo-detail-item__image"
                                wire:navigate
                            >
                                @if ($image)
                                    <img src="{{ $image }}" alt="{{ $product?->name }}">
                                @else
                                    <span>No Image</span>
                                @endif
                            </a>

                            <div class="combo-detail-item__content">
                                <h3>{{ $product?->name ?? 'Produk tidak ditemukan' }}</h3>

                                <p>
                                    {{ $product?->brand?->name ?? $product?->category?->name ?? 'Produk Paket' }}
                                </p>

                                <div>
                                    <span>Qty : {{ $item->quantity }}</span>
                                    <strong>{{ $product?->formatted_final_price }}</strong>
                                </div>

                                <div>
                                    <span>Subtotal</span>
                                    <strong>{{ $this->formatRupiah($item->line_total) }}</strong>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        </main>
    @endif
</div>