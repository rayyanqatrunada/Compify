<?php

use App\Models\EventSetting;
use App\Models\EventHeroImage;
use App\Models\EventFullBanner;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('components.layouts.admin')]
#[Title('Atur Event - Compify')]
class extends Component
{
    use WithFileUploads;

    // --- Tab ---
    public string $activeTab = 'settings';

    // --- Event Settings ---
    public ?EventSetting $event = null;

    public string $title = 'Flash Sale';
    public ?string $subtitle = 'berakhir dalam';
    public bool $is_active = false;
    public ?string $starts_at = null;
    public ?string $ends_at = null;

    public string $show_hero_section = '1';
    public string $show_flash_sale_section = '1';
    public string $show_full_banner_section = '1';
    public string $show_combo_package_section = '1';

    // --- Hero Images ---
    public ?string $main_link_url = null;
    public ?string $side_top_link_url = null;
    public ?string $side_bottom_link_url = null;

    public $main_image = null;
    public $side_top_image = null;
    public $side_bottom_image = null;

    public ?string $current_main_image = null;
    public ?string $current_side_top_image = null;
    public ?string $current_side_bottom_image = null;

    // --- Full Banner ---
    public ?EventFullBanner $banner = null;

    public $banner_image = null;
    public ?string $current_banner_image = null;
    public ?string $redirect_url = null;

    public function mount(): void
    {
        // Event Settings
        $this->event = EventSetting::query()->firstOrCreate([], [
            'title' => 'Flash Sale',
            'subtitle' => 'berakhir dalam',
            'is_active' => false,
            'show_hero_section' => true,
            'show_flash_sale_section' => true,
            'show_full_banner_section' => true,
            'show_combo_package_section' => true,
        ]);

        $this->title = $this->event->title ?: 'Flash Sale';
        $this->subtitle = $this->event->subtitle;
        $this->is_active = (bool) $this->event->is_active;
        $this->starts_at = $this->event->starts_at?->format('Y-m-d\TH:i');
        $this->ends_at = $this->event->ends_at?->format('Y-m-d\TH:i');

        $this->show_hero_section = $this->event->show_hero_section ? '1' : '0';
        $this->show_flash_sale_section = $this->event->show_flash_sale_section ? '1' : '0';
        $this->show_full_banner_section = $this->event->show_full_banner_section ? '1' : '0';
        $this->show_combo_package_section = $this->event->show_combo_package_section ? '1' : '0';

        // Hero Images
        $this->ensureHeroRows();
        $this->loadHeroImages();

        // Full Banner
        $this->banner = EventFullBanner::query()->firstOrCreate(
            ['sort_order' => 0],
            [
                'title' => null,
                'subtitle' => null,
                'description' => null,
                'image' => null,
                'button_text' => null,
                'button_url' => null,
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        $this->loadBanner();
    }

    // =========================================================
    // SAVE — Event Settings
    // =========================================================

    public function saveSettings(): void
    {
        $this->validate([
            'title'                      => ['required', 'string', 'max:120'],
            'subtitle'                   => ['nullable', 'string', 'max:160'],
            'is_active'                  => ['boolean'],
            'starts_at'                  => ['nullable', 'date'],
            'ends_at'                    => ['nullable', 'date', 'after_or_equal:starts_at'],
            'show_hero_section'          => ['required', 'in:0,1'],
            'show_flash_sale_section'    => ['required', 'in:0,1'],
            'show_full_banner_section'   => ['required', 'in:0,1'],
            'show_combo_package_section' => ['required', 'in:0,1'],
        ]);

        $this->event->update([
            'title'                      => $this->title,
            'subtitle'                   => $this->subtitle ?: null,
            'is_active'                  => $this->is_active,
            'starts_at'                  => $this->starts_at ?: null,
            'ends_at'                    => $this->ends_at ?: null,
            'show_hero_section'          => $this->show_hero_section === '1',
            'show_flash_sale_section'    => $this->show_flash_sale_section === '1',
            'show_full_banner_section'   => $this->show_full_banner_section === '1',
            'show_combo_package_section' => $this->show_combo_package_section === '1',
        ]);

        $this->event->refresh();

        session()->flash('success', 'Pengaturan event berhasil disimpan.');
    }

    // =========================================================
    // SAVE — Hero Images
    // =========================================================

    public function saveHero(): void
    {
        $this->validate([
            'main_link_url'        => ['nullable', 'string', 'max:255'],
            'side_top_link_url'    => ['nullable', 'string', 'max:255'],
            'side_bottom_link_url' => ['nullable', 'string', 'max:255'],
            'main_image'           => ['nullable', 'image', 'max:3072'],
            'side_top_image'       => ['nullable', 'image', 'max:3072'],
            'side_bottom_image'    => ['nullable', 'image', 'max:3072'],
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

        $this->reset(['main_image', 'side_top_image', 'side_bottom_image']);
        $this->loadHeroImages();

        session()->flash('success', 'Hero image event berhasil disimpan.');
    }

    private function ensureHeroRows(): void
    {
        EventHeroImage::query()->firstOrCreate(
            ['position' => EventHeroImage::POSITION_MAIN],
            ['link_url' => null, 'is_active' => true, 'sort_order' => 0]
        );

        EventHeroImage::query()->firstOrCreate(
            ['position' => EventHeroImage::POSITION_SIDE_TOP],
            ['link_url' => null, 'is_active' => true, 'sort_order' => 1]
        );

        EventHeroImage::query()->firstOrCreate(
            ['position' => EventHeroImage::POSITION_SIDE_BOTTOM],
            ['link_url' => null, 'is_active' => true, 'sort_order' => 2]
        );
    }

    private function loadHeroImages(): void
    {
        $main       = $this->heroByPosition(EventHeroImage::POSITION_MAIN);
        $sideTop    = $this->heroByPosition(EventHeroImage::POSITION_SIDE_TOP);
        $sideBottom = $this->heroByPosition(EventHeroImage::POSITION_SIDE_BOTTOM);

        $this->main_link_url        = $main?->link_url;
        $this->side_top_link_url    = $sideTop?->link_url;
        $this->side_bottom_link_url = $sideBottom?->link_url;

        $this->current_main_image        = $main?->image;
        $this->current_side_top_image    = $sideTop?->image;
        $this->current_side_bottom_image = $sideBottom?->image;
    }

    private function heroByPosition(string $position): ?EventHeroImage
    {
        return EventHeroImage::query()->where('position', $position)->first();
    }

    private function saveHeroPosition(
        string $position,
        ?string $linkUrl,
        mixed $uploadedImage,
        ?string $currentImage,
        int $sortOrder
    ): void {
        $data = [
            'title'      => null,
            'subtitle'   => null,
            'link_url'   => $linkUrl,
            'is_active'  => true,
            'sort_order' => $sortOrder,
        ];

        if ($uploadedImage) {
            if ($currentImage) {
                Storage::disk('public')->delete($currentImage);
            }
            $data['image'] = $uploadedImage->store('event/hero', 'public');
        }

        EventHeroImage::query()->updateOrCreate(['position' => $position], $data);
    }

    // =========================================================
    // SAVE — Full Banner
    // =========================================================

    public function saveBanner(): void
    {
        $this->validate([
            'banner_image' => ['nullable', 'image', 'max:4096'],
            'redirect_url' => ['nullable', 'string', 'max:255'],
        ]);

        $data = [
            'title'       => null,
            'subtitle'    => null,
            'description' => null,
            'button_text' => null,
            'button_url'  => $this->redirect_url,
            'is_active'   => true,
            'sort_order'  => 0,
        ];

        if ($this->banner_image) {
            if ($this->current_banner_image) {
                Storage::disk('public')->delete($this->current_banner_image);
            }
            $data['image'] = $this->banner_image->store('event/full-banners', 'public');
        }

        $this->banner->update($data);

        $this->banner_image = null;
        $this->loadBanner();

        session()->flash('success', 'Full banner berhasil disimpan.');
    }

    public function removeBannerImage(): void
    {
        if ($this->banner?->image) {
            Storage::disk('public')->delete($this->banner->image);
        }

        $this->banner->update(['image' => null]);

        $this->banner_image = null;
        $this->loadBanner();

        session()->flash('success', 'Gambar full banner berhasil dihapus.');
    }

    private function loadBanner(): void
    {
        $this->banner->refresh();
        $this->current_banner_image = $this->banner->image;
        $this->redirect_url         = $this->banner->button_url;
    }

    // =========================================================
    // HELPERS
    // =========================================================

    public function imageUrl(?string $path): ?string
    {
        return $path ? Storage::url($path) : null;
    }
};
?>

<div class="admin-page-v2 admin-event-page-v2">
    <div class="admin-section-title-v2">
        <div>
            <h2>Event Settings</h2>
            <p>Kelola pengaturan event, hero images, dan full banner dalam satu halaman.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="admin-alert-v2 admin-alert-v2--success">
            {{ session('success') }}
        </div>
    @endif

    {{-- TABS --}}
    <div class="admin-tabs-v2">
        <button
            type="button"
            @class(['admin-tab-v2', 'active' => $activeTab === 'settings'])
            wire:click="$set('activeTab', 'settings')"
        >
            Atur Event
        </button>
        <button
            type="button"
            @class(['admin-tab-v2', 'active' => $activeTab === 'hero'])
            wire:click="$set('activeTab', 'hero')"
        >
            Hero Images
        </button>
        <button
            type="button"
            @class(['admin-tab-v2', 'active' => $activeTab === 'banner'])
            wire:click="$set('activeTab', 'banner')"
        >
            Full Banner
        </button>
    </div>

    {{-- ==================== TAB: ATUR EVENT ==================== --}}
    @if ($activeTab === 'settings')
        <form wire:submit="saveSettings" class="admin-panel-v2 admin-form-v2">
            <div class="admin-grid-v2 admin-grid-v2--event-settings">
                <label>
                    <span>Judul Event</span>
                    <input type="text" wire:model="title" placeholder="Flash Sale">
                    @error('title')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <label>
                    <span>Subtitle Countdown</span>
                    <input type="text" wire:model="subtitle" placeholder="berakhir dalam">
                    @error('subtitle')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <label>
                    <span>Mulai Event</span>
                    <input type="datetime-local" wire:model="starts_at">
                    @error('starts_at')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <label>
                    <span>Berakhir Event</span>
                    <input type="datetime-local" wire:model="ends_at">
                    @error('ends_at')
                        <small>{{ $message }}</small>
                    @enderror
                </label>
            </div>

            <label class="admin-check-v2">
                <input type="checkbox" wire:model="is_active">
                <span>Aktifkan event</span>
            </label>

            <div class="admin-help-v2">
                Event hanya tampil di halaman shop jika status aktif dan waktu sekarang berada di antara waktu mulai dan waktu berakhir.
            </div>

            <br>

            <div class="admin-panel-v2" style="box-shadow:none; padding: 0;">
                <h3>Section yang Ditampilkan</h3>

                <div class="admin-grid-v2 admin-grid-v2--event-settings">
                    <label>
                        <span>Hero Event</span>
                        <select wire:model="show_hero_section">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                        @error('show_hero_section')
                            <small>{{ $message }}</small>
                        @enderror
                    </label>

                    <label>
                        <span>Flash Sale</span>
                        <select wire:model="show_flash_sale_section">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                        @error('show_flash_sale_section')
                            <small>{{ $message }}</small>
                        @enderror
                    </label>

                    <label>
                        <span>Full Banner Event</span>
                        <select wire:model="show_full_banner_section">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                        @error('show_full_banner_section')
                            <small>{{ $message }}</small>
                        @enderror
                    </label>

                    <label>
                        <span>Paket Bundling</span>
                        <select wire:model="show_combo_package_section">
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                        @error('show_combo_package_section')
                            <small>{{ $message }}</small>
                        @enderror
                    </label>
                </div>
            </div>

            <div class="admin-actions-v2">
                <button type="submit" class="admin-btn-v2 admin-btn-v2--primary">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    @endif

    {{-- ==================== TAB: HERO IMAGES ==================== --}}
    @if ($activeTab === 'hero')
        <form wire:submit="saveHero" class="admin-panel-v2 admin-form-v2">
            <div class="admin-hero-grid-v2">
                <div class="admin-hero-card-v2 admin-hero-card-v2--large">
                    <div>
                        <h3>Hero Besar Kiri</h3>
                        <p>Ukuran rekomendasi: landscape besar, sekitar 1688 × 640 px.</p>
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
                        <p>Ukuran rekomendasi: landscape kecil, sekitar 844 × 306 px.</p>
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
                        <p>Ukuran rekomendasi: landscape kecil, sekitar 844 × 306 px.</p>
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
    @endif

    {{-- ==================== TAB: FULL BANNER ==================== --}}
    @if ($activeTab === 'banner')
        <form wire:submit="saveBanner" class="admin-panel-v2 admin-form-v2">
            <div class="admin-full-banner-setting-v2">
                <div class="admin-full-banner-preview-v2">
                    @if ($banner_image)
                        <img src="{{ $banner_image->temporaryUrl() }}" alt="Preview full banner">
                    @elseif ($current_banner_image)
                        <img src="{{ $this->imageUrl($current_banner_image) }}" alt="Full banner">
                    @else
                        <span>Belum ada full banner</span>
                    @endif
                </div>

                <div class="admin-full-banner-fields-v2">
                    <label>
                        <span>Image Full Banner</span>
                        <input type="file" wire:model="banner_image" accept="image/*">
                        @error('banner_image')
                            <small>{{ $message }}</small>
                        @enderror
                    </label>

                    <label>
                        <span>Redirect Link</span>
                        <input type="text" wire:model="redirect_url" placeholder="/products atau /event/packages/nama-paket">
                        @error('redirect_url')
                            <small>{{ $message }}</small>
                        @enderror
                    </label>

                    <div class="admin-help-v2">
                        Rekomendasi ukuran banner: lebar penuh, sekitar <strong>2560 × 740 px</strong>.
                        Redirect link boleh diisi seperti <strong>/products</strong>, <strong>/event</strong>, atau URL halaman lain.
                    </div>

                    <div class="admin-actions-v2">
                        <button type="submit" class="admin-btn-v2 admin-btn-v2--primary">
                            Simpan Full Banner
                        </button>

                        @if ($current_banner_image)
                            <button
                                type="button"
                                wire:click="removeBannerImage"
                                wire:confirm="Hapus gambar full banner?"
                                class="admin-btn-v2 admin-btn-v2--danger"
                            >
                                Hapus Gambar
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>