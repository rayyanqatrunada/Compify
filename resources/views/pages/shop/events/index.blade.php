<?php

use App\Models\ComboPackage;
use App\Models\EventFlashSaleItem;
use App\Models\EventFullBanner;
use App\Models\EventHeroImage;
use App\Models\EventSetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.shop')]
#[Title('Event - Compify')]
class extends Component {
    #[Computed]
    public function event(): ?EventSetting
    {
        return EventSetting::activeNow();
    }

    #[Computed]
        public function heroImages()
    {
        return EventHeroImage::query()
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('position');
    }

    #[Computed]
    public function flashSaleItems()
    {
        return EventFlashSaleItem::query()
            ->with(['product.brand', 'product.category', 'group'])
            ->join('event_flash_sale_groups', 'event_flash_sale_items.event_flash_sale_group_id', '=', 'event_flash_sale_groups.id')
            ->select('event_flash_sale_items.*')
            ->where('event_flash_sale_items.is_active', true)
            ->where('event_flash_sale_groups.is_active', true)
            ->whereHas('product', function ($query) {
                $query->where('products.is_active', true);
            })
            ->orderBy('event_flash_sale_groups.sort_order')
            ->orderBy('event_flash_sale_items.sort_order')
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function fullBanner(): ?EventFullBanner
    {
        return EventFullBanner::query()
            ->active()
            ->orderBy('sort_order')
            ->first();
    }

    #[Computed]
    public function comboPackages()
    {
        return ComboPackage::query()
            ->with(['items.product.brand', 'items.product.category'])
            ->active()
            ->whereHas('items')
            ->orderBy('sort_order')
            ->limit(4)
            ->get();
    }

    public function countdownParts(): array
    {
        $event = $this->event;

        if (! $event?->ends_at) {
            return ['00', '00', '00'];
        }
                $seconds = max(0, now()->diffInSeconds($event->ends_at, false));

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return [
            str_pad((string) $hours, 2, '0', STR_PAD_LEFT),
            str_pad((string) $minutes, 2, '0', STR_PAD_LEFT),
            str_pad((string) $secs, 2, '0', STR_PAD_LEFT),
        ];
    }

    public function heroImage(string $position): ?EventHeroImage
    {
        return $this->heroImages->get($position)?->first();
    }

    public function imageUrl(?string $path): ?string
    {
        return $path ? Storage::url($path) : null;
    }
};
?>

<div class="event-page" wire:poll.1000ms>
    @if(! $this->event)
        <section class="event-empty">
            <div class="event-empty__box">
                <p>Event</p>
                <h1>Tidak ada event saat ini</h1>
                <span>Event belum dimulai atau sudah berakhir. Silakan cek kembali nanti.</span>
                <a href="{{ route('products.index') }}" wire:navigate>Lihat Produk</a>
            </div>
        </section>
    @else
        @php
            [$hours, $minutes, $seconds] = $this->countdownParts();
            $mainHero = $this->heroImage('main');
            $topHero = $this->heroImage('side_top');
            $bottomHero = $this->heroImage('side_bottom');
            $fullBanner = $this->fullBanner;
        @endphp

        <section class="event-countdown-bar">
            <strong>{{ $this->event->title }}</strong>
                        <span>⏱ {{ $this->event->subtitle ?: 'berakhir dalam' }}</span>
            <div class="event-timer">
                <b>{{ $hours }}</b>
                <b>{{ $minutes }}</b>
                <b>{{ $seconds }}</b>
            </div>
        </section>

        <section class="event-hero-wrap">
            <a href="{{ $mainHero?->link_url ?: route('products.index') }}" class="event-hero-card event-hero-card--large"
               @if($this->imageUrl($mainHero?->image)) style="background-image: url('{{ $this->imageUrl($mainHero?->image) }}')" @endif wire:navigate>
                <div>
                    @if($mainHero?->title)<h2>{{ $mainHero->title }}</h2>@endif
                    @if($mainHero?->subtitle)<p>{{ $mainHero->subtitle }}</p>@endif
                </div>
            </a>

            <div class="event-hero-side">
                <a href="{{ $topHero?->link_url ?: route('products.index') }}" class="event-hero-card"
                   @if($this->imageUrl($topHero?->image)) style="background-image: url('{{ $this->imageUrl($topHero?->image) }}')" @endif wire:navigate>
                    <div>
                        @if($topHero?->title)<h3>{{ $topHero->title }}</h3>@endif
                        @if($topHero?->subtitle)<p>{{ $topHero->subtitle }}</p>@endif
                    </div>
                </a>

                <a href="{{ $bottomHero?->link_url ?: route('products.index') }}" class="event-hero-card"
                   @if($this->imageUrl($bottomHero?->image)) style="background-image: url('{{ $this->imageUrl($bottomHero?->image) }}')" @endif wire:navigate>
                    <div>
                        @if($bottomHero?->title)<h3>{{ $bottomHero->title }}</h3>@endif
                        @if($bottomHero?->subtitle)<p>{{ $bottomHero->subtitle }}</p>@endif
                    </div>
                </a>
            </div>
        </section>

        <section class="event-section event-flash-section">
            <div class="event-section-head">
                <div class="event-head-left">
                    <h2>Flash Sale</h2>
                    <div class="event-timer event-timer--small">
                        <b>{{ $hours }}</b><b>{{ $minutes }}</b><b>{{ $seconds }}</b>
                    </div>
                </div>
                <a href="{{ route('products.index') }}" wire:navigate>Lihat Semua →</a>
            </div>

            <div class="event-flash-grid">
                @forelse($this->flashSaleItems as $item)
                    @php
                        $product = $item->product;
                        $image = $this->imageUrl($product?->image);
                    @endphp

                    <article class="event-product-card">
                        @if($item->discount_percent > 0)
                            <span class="event-discount-badge">{{ $item->discount_percent }}%</span>
                        @endif

                        <a href="{{ route('products.show', $product) }}" class="event-product-image" wire:navigate>
                            @if($image)<img src="{{ $image }}" alt="{{ $product->name }}">@endif
                        </a>

                        <div class="event-product-content">
                            <h3>{{ $product->name }}</h3>
                            <div class="event-product-price-old">{{ $item->formatted_base_price }}</div>
                            <strong>{{ $item->formatted_event_price }}</strong>

                            <div class="event-product-footer">
                                <span>{{ $item->stock_limit ? 'Stok event: ' . $item->stock_limit : 'Stok terbatas' }}</span>
                                <form method="POST" action="{{ route('cart.add', $product) }}">
                                    @csrf

                                    <input type="hidden" name="quantity" value="1">

                                    <button type="submit">Beli</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="event-empty-inline">Belum ada produk flash sale.</div>
                @endforelse
            </div>
        </section>

        @if($fullBanner?->image)
            <a
                href="{{ $fullBanner->button_url ?: route('products.index') }}"
                class="event-full-banner event-full-banner--image-only"
                style="background-image: url('{{ $this->imageUrl($fullBanner->image) }}')"
                wire:navigate
            >
                <span class="sr-only">Full Banner Event</span>
            </a>
        @endif

        <section class="event-section event-combo-section">
            <div class="event-section-head">
                <h2>Paket Bundling</h2>
                <a href="#paket">Lihat Semua →</a>
            </div>

            <div class="event-combo-grid" id="paket">
                @forelse($this->comboPackages as $package)
                    <article class="event-combo-card">
                        @if($package->savings > 0)
                            <span class="event-combo-save">Hemat {{ $package->formatted_savings }}</span>
                        @endif

                        <div class="event-combo-head">
                            <h3>{{ $package->name }}</h3>
                            <p>{{ $package->subtitle ?: 'Paket pilihan untuk kebutuhanmu' }}</p>
                        </div>

                        <div class="event-combo-items">
                            @foreach($package->items->take(2) as $item)
                                @php
                                    $product = $item->product;
                                    $image = $this->imageUrl($product?->image);
                                @endphp

                                <div class="event-combo-item">
                                    <div class="event-combo-thumb">
                                        @if($image)<img src="{{ $image }}" alt="{{ $product?->name }}">@endif
                                    </div>

                                    <div>
                                        <strong>{{ $product?->name }}</strong>
                                        <p>{{ $product?->category?->name ?? 'Produk paket' }}</p>
                                        <span>{{ $product?->formatted_final_price }} — Dalam Paket</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if($package->items->count() > 2)
                            <a class="event-combo-more" href="{{ route('event.packages.show', $package) }}" wire:navigate>View more →</a>
                        @endif

                        <div class="event-combo-footer">
                            <div>
                                <small>Total Satuan: {{ $package->formatted_original_total }}</small>
                                <span>Harga Paket <strong>{{ $package->formatted_package_price }}</strong></span>
                            </div>
                            <a href="{{ route('event.packages.show', $package) }}" wire:navigate>
                                Beli Paket
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="event-empty-inline">Belum ada paket kombo.</div>
                @endforelse
            </div>
        </section>
    @endif
</div>


