<?php

use App\Models\EventFullBanner;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('components.layouts.admin')]
#[Title('Full Banner Event - Compify')]
class extends Component
{
    use WithFileUploads;

    public ?EventFullBanner $banner = null;

    public $image = null;
    public ?string $current_image = null;
    public ?string $redirect_url = null;

    public function mount(): void
    {
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

    public function save(): void
    {
        $this->validate([
            'image' => ['nullable', 'image', 'max:4096'],
            'redirect_url' => ['nullable', 'string', 'max:255'],
        ]);

        $data = [
            'title' => null,
            'subtitle' => null,
            'description' => null,
            'button_text' => null,
            'button_url' => $this->redirect_url,
            'is_active' => true,
            'sort_order' => 0,
        ];

        if ($this->image) {
            if ($this->current_image) {
                Storage::disk('public')->delete($this->current_image);
            }

            $data['image'] = $this->image->store('event/full-banners', 'public');
        }

        $this->banner->update($data);

        $this->image = null;
        $this->loadBanner();

        session()->flash('success', 'Full banner berhasil disimpan.');
    }

    public function removeImage(): void
    {
        if ($this->banner?->image) {
            Storage::disk('public')->delete($this->banner->image);
        }

        $this->banner->update([
            'image' => null,
        ]);

        $this->image = null;
        $this->loadBanner();

        session()->flash('success', 'Gambar full banner berhasil dihapus.');
    }

    private function loadBanner(): void
    {
        $this->banner->refresh();

        $this->current_image = $this->banner->image;
        $this->redirect_url = $this->banner->button_url;
    }

    public function imageUrl(?string $path): ?string
    {
        return $path ? Storage::url($path) : null;
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <div>
            <h2>Full Banner</h2>
            <p>Atur gambar full banner dan link tujuan saat banner ditekan.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="admin-alert-v2 admin-alert-v2--success">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="admin-panel-v2 admin-form-v2">
        <div class="admin-full-banner-setting-v2">
            <div class="admin-full-banner-preview-v2">
                @if ($image)
                    <img src="{{ $image->temporaryUrl() }}" alt="Preview full banner">
                @elseif ($current_image)
                    <img src="{{ $this->imageUrl($current_image) }}" alt="Full banner">
                @else
                    <span>Belum ada full banner</span>
                @endif
            </div>

            <div class="admin-full-banner-fields-v2">
                <label>
                    <span>Image Full Banner</span>
                    <input type="file" wire:model="image" accept="image/*">

                    @error('image')
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
                    Rekomendasi ukuran banner: lebar penuh, sekitar <strong>1440 × 446 px</strong>.
                    Redirect link boleh diisi seperti <strong>/products</strong>, <strong>/event</strong>, atau URL halaman lain.
                </div>

                <div class="admin-actions-v2">
                    <button type="submit" class="admin-btn-v2 admin-btn-v2--primary">
                        Simpan Full Banner
                    </button>

                    @if ($current_image)
                        <button
                            type="button"
                            wire:click="removeImage"
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
</div>