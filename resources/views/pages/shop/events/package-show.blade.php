<?php

use App\Models\ComboPackage;
use App\Models\EventSetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.shop')]
#[Title('Detail Paket Kombo - Compify')]
class extends Component {
    public ComboPackage $comboPackage;

    public function mount(ComboPackage $comboPackage): void
    {
        abort_unless($comboPackage->is_active, 404);

        $this->comboPackage = $comboPackage->load(['items.product.brand', 'items.product.category']);
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
    @if(! $this->event)
        <section class="event-empty">
            <div class="event-empty__box">
                <p>Event</p>
                <h1>Tidak ada event saat ini</h1>
                <span>Paket kombo hanya bisa dilihat saat event berjalan.</span>
                <a href="{{ route('products.index') }}" wire:navigate>Lihat Produk</a>
            </div>
        </section>
    @else
        <section class="event-package-detail">
            <div class="event-package-detail__media">
                @if($this->imageUrl($comboPackage->image))
                    <img src="{{ $this->imageUrl($comboPackage->image) }}" alt="{{ $comboPackage->name }}">
                @else
                    <div class="event-package-placeholder">{{ strtoupper(substr($comboPackage->name, 0, 2)) }}</div>
                @endif
            </div>

            <div class="event-package-detail__info">
                <a href="{{ route('event.index') }}" class="event-back-link" wire:navigate>← Kembali ke Event</a>
                <h1>{{ $comboPackage->name }}</h1>
                <p>{{ $comboPackage->subtitle ?: 'Paket kombo pilihan dari Compify.' }}</p>

                @if($comboPackage->description)
                    <div class="event-package-desc">{{ $comboPackage->description }}</div>
                @endif

                <div class="event-package-price-box">
                    <span>Total Satuan</span>
                    <del>{{ $comboPackage->formatted_original_total }}</del>
                    <span>Harga Paket</span>
                    <strong>{{ $comboPackage->formatted_package_price }}</strong>
                    @if($comboPackage->savings > 0)
                        <b>Hemat {{ $comboPackage->formatted_savings }}</b>
                    @endif
                </div>
            </div>
        </section>

        <section class="event-section event-package-items-section">
            <div class="event-section-head">
                <h2>Produk Dalam Paket</h2>
            </div>

            <div class="event-package-items">
                @foreach($comboPackage->items as $item)
                    @php
                        $product = $item->product;
                        $image = $this->imageUrl($product?->image);
                    @endphp

                    <article class="event-package-item-card">
                        <a href="{{ $product ? route('products.show', $product) : '#' }}" class="event-package-item-card__image" wire:navigate>
                            @if($image)
                                <img src="{{ $image }}" alt="{{ $product?->name }}">
                            @endif
                        </a>

                        <div>
                            <h3>{{ $product?->name ?? 'Produk tidak ditemukan' }}</h3>
                            <p>{{ $product?->brand?->name ?? $product?->category?->name }}</p>
                            <span>Qty: {{ $item->quantity }}</span>
                            <strong>{{ $product?->formatted_final_price }}</strong>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="event-package-note">
                <strong>Catatan:</strong>
                tombol checkout khusus paket bisa ditambahkan setelah cart/checkout mendukung harga paket. Untuk tahap ini halaman detail sudah menampilkan semua isi paket dan harga paket dari admin.
            </div>
        </section>
    @endif
</div>