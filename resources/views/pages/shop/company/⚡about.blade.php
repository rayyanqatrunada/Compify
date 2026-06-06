<?php

use App\Models\AboutSection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.shop')]
#[Title('About Us - Compify')]
class extends Component {
    #[Computed]
    public function hero(): ?AboutSection
    {
        return AboutSection::type(AboutSection::TYPE_HERO)
            ->active()
            ->orderBy('sort_order')
            ->first();
    }

    #[Computed]
    public function intro(): ?AboutSection
    {
        return AboutSection::type(AboutSection::TYPE_INTRO)
            ->active()
            ->orderBy('sort_order')
            ->first();
    }

    #[Computed]
    public function stats()
    {
        return AboutSection::type(AboutSection::TYPE_STATS)
            ->active()
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function quote(): ?AboutSection
    {
        return AboutSection::type(AboutSection::TYPE_QUOTE)
            ->active()
            ->orderBy('sort_order')
            ->first();
    }

    #[Computed]
    public function values()
    {
        return AboutSection::type(AboutSection::TYPE_VALUE)
            ->active()
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function banner(): ?AboutSection
    {
        return AboutSection::type(AboutSection::TYPE_BANNER)
            ->active()
            ->orderBy('sort_order')
            ->first();
    }

    #[Computed]
    public function histories()
    {
        return AboutSection::type(AboutSection::TYPE_HISTORY)
            ->active()
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function testimonials()
    {
        return AboutSection::type(AboutSection::TYPE_TESTIMONIAL)
            ->active()
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function heroExists(): bool
    {
        return AboutSection::type(AboutSection::TYPE_HERO)->exists();
    }

    #[Computed]
    public function introExists(): bool
    {
        return AboutSection::type(AboutSection::TYPE_INTRO)->exists();
    }

    #[Computed]
    public function statsExist(): bool
    {
        return AboutSection::type(AboutSection::TYPE_STATS)->exists();
    }

    #[Computed]
    public function quoteExists(): bool
    {
        return AboutSection::type(AboutSection::TYPE_QUOTE)->exists();
    }

    #[Computed]
    public function valuesExist(): bool
    {
        return AboutSection::type(AboutSection::TYPE_VALUE)->exists();
    }

    #[Computed]
    public function bannerExists(): bool
    {
        return AboutSection::type(AboutSection::TYPE_BANNER)->exists();
    }

    #[Computed]
    public function historiesExist(): bool
    {
        return AboutSection::type(AboutSection::TYPE_HISTORY)->exists();
    }

    #[Computed]
    public function testimonialsExist(): bool
    {
        return AboutSection::type(AboutSection::TYPE_TESTIMONIAL)->exists();
    }
};
?>

@php
    $hero = $this->hero;
    $intro = $this->intro;
    $quote = $this->quote;
    $banner = $this->banner;

    $heroImage = $hero?->image ? Storage::url($hero->image) : null;

    $introDescription = $intro?->description
        ?: 'Compify adalah reseller resmi perlengkapan dan sparepart komputer terlengkap bergaransi resmi. Kami menghadirkan komponen orisinal berkualitas dengan proses belanja praktis dan pengiriman aman. Compify siap jadi partner andalan untuk penuhi segala kebutuhan rakitan dan digitalmu.';

    $introButtonText = $intro?->button_text ?: 'Belanja Sekarang';
    $introButtonUrl = $intro?->button_url ?: route('products.index');

    $stats = $this->stats
        ->map(fn ($item) => [
            'value' => $item->stat_value ?: '0',
            'label' => $item->title ?: 'Stat',
        ])
        ->values();

    if ($stats->isEmpty() && ! $this->statsExist) {
        $stats = collect([
            ['value' => '99+', 'label' => 'Produk'],
            ['value' => '99+', 'label' => 'Partner'],
            ['value' => '99+', 'label' => 'Pelanggan'],
        ]);
    }

    $quoteDescription = $quote?->description
        ?: 'Compify dibangun di atas satu ide sederhana yaitu penyediaan komponen komputer harus cepat dan andal. Kami fokus menjaga kelancaran bisnis digital Anda.';

    $values = $this->values
        ->map(fn ($item) => [
            'title' => $item->title ?: 'Value',
            'description' => $item->description ?: '',
            'icon' => $item->icon ?: strtoupper(substr($item->title ?: 'V', 0, 2)),
        ])
        ->values();

    if ($values->isEmpty() && ! $this->valuesExist) {
        $values = collect([
            [
                'title' => 'Keaslian',
                'description' => 'Semua suku cadang 100% orisinal dan dilindungi garansi resmi distributor.',
                'icon' => 'OK',
            ],
            [
                'title' => 'Ketersediaan',
                'description' => 'Stok komponen selalu siap untuk memastikan bisnis Anda tidak pernah tertunda.',
                'icon' => 'STK',
            ],
            [
                'title' => 'Keterjangkauan',
                'description' => 'Penawaran harga terbaik dan kompetitif khusus untuk kebutuhan reseller.',
                'icon' => 'IDR',
            ],
        ]);
    }

    $bannerImage = $banner?->image ? Storage::url($banner->image) : null;

    $histories = $this->histories
        ->map(fn ($item) => [
            'year' => $item->year ?: '-',
            'title' => $item->title ?: '',
            'description' => $item->description ?: '',
        ])
        ->values();

    if ($histories->isEmpty() && ! $this->historiesExist) {
        $histories = collect([
            [
                'year' => '2020',
                'title' => 'Compify Didirikan',
                'description' => 'Compify lahir dari semangat untuk menghadirkan komponen komputer orisinal yang mudah diakses oleh semua kalangan.',
            ],
            [
                'year' => '2021',
                'title' => 'Ekspansi Produk',
                'description' => 'Katalog produk diperluas mencakup ratusan SKU dari brand-brand ternama dunia.',
            ],
            [
                'year' => '2023',
                'title' => 'Ribuan Pelanggan',
                'description' => 'Compify telah melayani ribuan pelanggan setia dari seluruh Indonesia.',
            ],
        ]);
    }

    $testimonials = $this->testimonials
        ->map(fn ($item) => [
            'name' => $item->title ?: 'Pelanggan',
            'description' => $item->description ?: '',
            'rating' => $item->rating ?? 5,
            'avatar' => $item->image ? Storage::url($item->image) : null,
        ])
        ->values();

    if ($testimonials->isEmpty() && ! $this->testimonialsExist) {
        $testimonials = collect([
            [
                'name' => 'Andi Prasetyo',
                'description' => 'Produk original, pengiriman cepat, dan harga sangat kompetitif. Compify jadi pilihan utama untuk belanja komponen!',
                'rating' => 5,
                'avatar' => null,
            ],
            [
                'name' => 'Siti Rahma',
                'description' => 'Pelayanan ramah dan responsif. Barang sesuai deskripsi, garansi resmi. Sangat rekomendasikan!',
                'rating' => 5,
                'avatar' => null,
            ],
            [
                'name' => 'Budi Santoso',
                'description' => 'Stok lengkap dan selalu update. Senang berbelanja di Compify karena prosesnya mudah dan aman.',
                'rating' => 4,
                'avatar' => null,
            ],
        ]);
    }
@endphp

<section class="about-page">
    {{-- HERO --}}
    @if($hero || ! $this->heroExists)
        <section
            class="about-hero"
            @if($heroImage)
                style="background-image: linear-gradient(90deg, rgba(14,10,16,.78), rgba(14,10,16,.28)), url('{{ $heroImage }}')"
            @endif
        >
            <h1>{{ $hero?->title ?: 'About Us' }}</h1>
        </section>
    @endif

    <div class="about-container">
        {{-- INTRO --}}
        @if($intro || ! $this->introExists)
            <section class="about-intro">
                <h2>{!! nl2br(e($introDescription)) !!}</h2>

                <a href="{{ $introButtonUrl }}" class="shop-btn" wire:navigate>
                    {{ $introButtonText }} ->
                </a>
            </section>
        @endif

        {{-- STATS --}}
        @if($stats->isNotEmpty())
            <section class="about-stats">
                @foreach($stats as $stat)
                    <div class="stat-card">
                        <h3>{{ $stat['value'] }}</h3>
                        <p>{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </section>
        @endif

        {{-- QUOTE --}}
        @if($quote || ! $this->quoteExists)
            <section class="about-quote">
                <span class="quote-left">"</span>

                <h2>{!! nl2br(e($quoteDescription)) !!}</h2>

                <span class="quote-right">"</span>
            </section>
        @endif

        {{-- VALUES --}}
        @if($values->isNotEmpty())
            <section class="about-values">
                @foreach($values as $value)
                    <div class="value-card">
                        <div class="value-icon">{{ $value['icon'] }}</div>

                        <h3>{{ $value['title'] }}</h3>

                        <p>{{ $value['description'] }}</p>
                    </div>
                @endforeach
            </section>
        @endif

        {{-- BANNER TENGAH --}}
        @if($banner || ! $this->bannerExists)
            <section
                class="about-banner"
                @if($bannerImage)
                    style="background-image: linear-gradient(rgba(14,10,16,.65), rgba(14,10,16,.65)), url('{{ $bannerImage }}')"
                @endif
            >
                <div class="about-banner-content">
                    <h2>{{ $banner?->title ?: 'Jadilah Bagian dari Compify' }}</h2>

                    @if($banner?->subtitle)
                        <p>{{ $banner->subtitle }}</p>
                    @endif

                    @if($banner?->button_text && $banner?->button_url)
                        <a href="{{ $banner->button_url }}" class="shop-btn" wire:navigate>
                            {{ $banner->button_text }} ->
                        </a>
                    @elseif(! $this->bannerExists)
                        <a href="{{ route('products.index') }}" class="shop-btn" wire:navigate>
                            Mulai Belanja ->
                        </a>
                    @endif
                </div>
            </section>
        @endif

        {{-- SEJARAH / TIMELINE --}}
        @if($histories->isNotEmpty())
            <section class="about-history">
                <h2 class="about-section-heading">Sejarah Compify</h2>

                <div class="history-timeline">
                    @foreach($histories as $index => $history)
                        <div class="history-item {{ $index % 2 === 0 ? 'history-item--left' : 'history-item--right' }}">
                            <div class="history-year">{{ $history['year'] }}</div>
                            <div class="history-dot"></div>
                            <div class="history-card">
                                <h3>{{ $history['title'] }}</h3>
                                <p>{{ $history['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- TESTIMONI --}}
        @if($testimonials->isNotEmpty())
            <section class="about-testimonials">
                <h2 class="about-section-heading">Apa Kata Pelanggan</h2>

                <div class="testimonial-grid">
                    @foreach($testimonials as $testimonial)
                        <div class="testimonial-card">
                            <div class="testimonial-header">
                                <div class="testimonial-avatar">
                                    @if($testimonial['avatar'])
                                        <img src="{{ $testimonial['avatar'] }}" alt="{{ $testimonial['name'] }}">
                                    @else
                                        <span>{{ strtoupper(substr($testimonial['name'], 0, 1)) }}</span>
                                    @endif
                                </div>

                                <div class="testimonial-meta">
                                    <strong>{{ $testimonial['name'] }}</strong>
                                    <div class="testimonial-stars">
                                        @for($i = 1; $i <= 5; $i++)
                                            <span class="{{ $i <= $testimonial['rating'] ? 'star-filled' : 'star-empty' }}">★</span>
                                        @endfor
                                    </div>
                                </div>
                            </div>

                            <p>{{ $testimonial['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</section>