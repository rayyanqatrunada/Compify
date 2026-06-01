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
};
?>

@php
    $hero = $this->hero;
    $intro = $this->intro;
    $quote = $this->quote;

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
@endphp

<section class="about-page">
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
        @if($intro || ! $this->introExists)
            <section class="about-intro">
                <h2>{!! nl2br(e($introDescription)) !!}</h2>

                <a href="{{ $introButtonUrl }}" class="shop-btn" wire:navigate>
                    {{ $introButtonText }} ->
                </a>
            </section>
        @endif

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

        @if($quote || ! $this->quoteExists)
            <section class="about-quote">
                <span class="quote-left">"</span>

                <h2>{!! nl2br(e($quoteDescription)) !!}</h2>

                <span class="quote-right">"</span>
            </section>
        @endif

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
    </div>
</section>
