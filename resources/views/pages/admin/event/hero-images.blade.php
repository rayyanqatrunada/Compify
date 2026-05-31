<?php

use App\Models\EventHeroImage;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('components.layouts.admin')]
#[Title('Image Hero Event - Compify')]
class extends Component
{
    use WithFileUploads;

    public ?string $main_link_url = null;
    public ?string $side_top_link_url = null;
    public ?string $side_bottom_link_url = null;

    public $main_image = null;
    public $side_top_image = null;
    public $side_bottom_image = null;

    public ?string $current_main_image = null;
    public ?string $current_side_top_image = null;
    public ?string $current_side_bottom_image = null;

    public function mount(): void
    {
        $this->ensureHeroRows();
        $this->loadHeroImages();
    }

    public function save(): void
    {
        $this->validate([
            'main_link_url' => ['nullable', 'string', 'max:255'],
            'side_top_link_url' => ['nullable', 'string', 'max:255'],
            'side_bottom_link_url' => ['nullable', 'string', 'max:255'],

            'main_image' => ['nullable', 'image', 'max:3072'],
            'side_top_image' => ['nullable', 'image', 'max:3072'],
            'side_bottom_image' => ['nullable', 'image', 'max:3072'],
        ]);

        $this->saveHeroPosition(
            position: EventHeroImage::POSITION_MAIN,
            linkUrl: $this->main_link_url,
            uploadedImage: $this->main_image,
            currentImage: $this->current_main_image,
            sortOrder: 0
        );

        $this->saveHeroPosition(
            position: EventHeroImage::POSITION_SIDE_TOP,
            linkUrl: $this->side_top_link_url,
            uploadedImage: $this->side_top_image,
            currentImage: $this->current_side_top_image,
            sortOrder: 1
        );

        $this->saveHeroPosition(
            position: EventHeroImage::POSITION_SIDE_BOTTOM,
            linkUrl: $this->side_bottom_link_url,
            uploadedImage: $this->side_bottom_image,
            currentImage: $this->current_side_bottom_image,
            sortOrder: 2
        );

        $this->reset([
            'main_image',
            'side_top_image',
            'side_bottom_image',
        ]);

        $this->loadHeroImages();

        session()->flash('success', 'Hero image event berhasil disimpan.');
    }

    private function ensureHeroRows(): void
    {
        EventHeroImage::query()->firstOrCreate(
            ['position' => EventHeroImage::POSITION_MAIN],
            [
                'link_url' => null,
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        EventHeroImage::query()->firstOrCreate(
            ['position' => EventHeroImage::POSITION_SIDE_TOP],
            [
                'link_url' => null,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        EventHeroImage::query()->firstOrCreate(
            ['position' => EventHeroImage::POSITION_SIDE_BOTTOM],
            [
                'link_url' => null,
                'is_active' => true,
                'sort_order' => 2,
            ]
        );
    }

    private function loadHeroImages(): void
    {
        $main = $this->heroByPosition(EventHeroImage::POSITION_MAIN);
        $sideTop = $this->heroByPosition(EventHeroImage::POSITION_SIDE_TOP);
        $sideBottom = $this->heroByPosition(EventHeroImage::POSITION_SIDE_BOTTOM);

        $this->main_link_url = $main?->link_url;
        $this->side_top_link_url = $sideTop?->link_url;
        $this->side_bottom_link_url = $sideBottom?->link_url;

        $this->current_main_image = $main?->image;
        $this->current_side_top_image = $sideTop?->image;
        $this->current_side_bottom_image = $sideBottom?->image;
    }

    private function heroByPosition(string $position): ?EventHeroImage
    {
        return EventHeroImage::query()
            ->where('position', $position)
            ->first();
    }

    private function saveHeroPosition(
        string $position,
        ?string $linkUrl,
        mixed $uploadedImage,
        ?string $currentImage,
        int $sortOrder
    ): void {
        $data = [
            'title' => null,
            'subtitle' => null,
            'link_url' => $linkUrl,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ];

        if ($uploadedImage) {
            if ($currentImage) {
                Storage::disk('public')->delete($currentImage);
            }

            $data['image'] = $uploadedImage->store('event/hero', 'public');
        }

        EventHeroImage::query()->updateOrCreate(
            ['position' => $position],
            $data
        );
    }

    public function imageUrl(?string $path): ?string
    {
        return $path ? Storage::url($path) : null;
    }
};
?>

<div class="admin-page-v2 admin-event-page-v2">
    <div class="admin-section-title-v2">
        <div>
            <h2>Image Hero Event</h2>
            <p>Atur 3 foto hero event dan link tujuan saat foto ditekan.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="admin-alert-v2 admin-alert-v2--success">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="admin-panel-v2 admin-form-v2">
        <div class="admin-hero-grid-v2">
            <div class="admin-hero-card-v2 admin-hero-card-v2--large">
                <div>
                    <h3>Hero Besar Kiri</h3>
                    <p>Ukuran rekomendasi: landscape besar, sekitar 900 × 446 px.</p>
                </div>

                <div class="admin-hero-preview-v2 admin-hero-preview-v2--large">
                    @if ($main_image)
                        <img src="{{ $main_image->temporaryUrl() }}" alt="Preview hero besar">
                    @elseif ($current_main_image)
                        <img src="{{ $this->imageUrl($current_main_image) }}" alt="Hero besar">
                    @else
                        <span>Belum ada gambar</span>
                    @endif
                </div>

                <label>
                    <span>Foto Hero Besar</span>
                    <input type="file" wire:model="main_image" accept="image/*">
                    @error('main_image')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <label>
                    <span>Redirect Link</span>
                    <input type="text" wire:model="main_link_url" placeholder="/products">
                    @error('main_link_url')
                        <small>{{ $message }}</small>
                    @enderror
                </label>
            </div>

            <div class="admin-hero-card-v2">
                <div>
                    <h3>Hero Kanan Atas</h3>
                    <p>Ukuran rekomendasi: landscape kecil, sekitar 440 × 206 px.</p>
                </div>

                <div class="admin-hero-preview-v2">
                    @if ($side_top_image)
                        <img src="{{ $side_top_image->temporaryUrl() }}" alt="Preview hero kanan atas">
                    @elseif ($current_side_top_image)
                        <img src="{{ $this->imageUrl($current_side_top_image) }}" alt="Hero kanan atas">
                    @else
                        <span>Belum ada gambar</span>
                    @endif
                </div>

                <label>
                    <span>Foto Hero Kanan Atas</span>
                    <input type="file" wire:model="side_top_image" accept="image/*">
                    @error('side_top_image')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <label>
                    <span>Redirect Link</span>
                    <input type="text" wire:model="side_top_link_url" placeholder="/products">
                    @error('side_top_link_url')
                        <small>{{ $message }}</small>
                    @enderror
                </label>
            </div>

            <div class="admin-hero-card-v2">
                <div>
                    <h3>Hero Kanan Bawah</h3>
                    <p>Ukuran rekomendasi: landscape kecil, sekitar 440 × 224 px.</p>
                </div>

                <div class="admin-hero-preview-v2">
                    @if ($side_bottom_image)
                        <img src="{{ $side_bottom_image->temporaryUrl() }}" alt="Preview hero kanan bawah">
                    @elseif ($current_side_bottom_image)
                        <img src="{{ $this->imageUrl($current_side_bottom_image) }}" alt="Hero kanan bawah">
                    @else
                        <span>Belum ada gambar</span>
                    @endif
                </div>

                <label>
                    <span>Foto Hero Kanan Bawah</span>
                    <input type="file" wire:model="side_bottom_image" accept="image/*">
                    @error('side_bottom_image')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <label>
                    <span>Redirect Link</span>
                    <input type="text" wire:model="side_bottom_link_url" placeholder="/event">
                    @error('side_bottom_link_url')
                        <small>{{ $message }}</small>
                    @enderror
                </label>
            </div>
        </div>

        <div class="admin-help-v2">
            Redirect link boleh diisi seperti <strong>/products</strong>, <strong>/event</strong>, atau URL halaman lain. Kalau dikosongkan, gambar akan diarahkan ke halaman produk.
        </div>

        <div class="admin-actions-v2">
            <button type="submit" class="admin-btn-v2 admin-btn-v2--primary">
                Simpan Hero Image
            </button>
        </div>
    </form>
</div>