<?php

use App\Models\Banner;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts.admin')]
#[Title('Admin Banner - Compify')]
class extends Component {
    use WithFileUploads;

    public ?int $editingId = null;
    public string $title = '';
    public string $subtitle = '';
    public string $button_text = '';
    public string $button_url = '';
    public bool $is_active = true;
    public int $sort_order = 0;
    public string $asset_type = 'image'; // 'image' | 'video'
    public $imageFile = null;
    public $videoFile = null;

    #[Computed]
    public function banners()
    {
        return Banner::orderBy('sort_order')->latest()->get();
    }

    public function save(): void
    {
        $this->validate([
            'title'      => ['required', 'string', 'max:255'],
            'subtitle'   => ['nullable', 'string', 'max:255'],
            'button_text'=> ['nullable', 'string', 'max:255'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'is_active'  => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'asset_type' => ['required', 'in:image,video'],
            'imageFile'  => ['nullable', 'image', 'max:4096'],
            // max 60 MB, format umum
            'videoFile'  => ['nullable', 'mimetypes:video/mp4,video/webm,video/ogg', 'max:61440'],
        ]);

        $payload = [
            'title'      => $this->title,
            'subtitle'   => $this->subtitle,
            'button_text'=> $this->button_text,
            'button_url' => $this->button_url,
            'is_active'  => $this->is_active,
            'sort_order' => $this->sort_order,
            'asset_type' => $this->asset_type,
        ];

        if ($this->asset_type === 'image' && $this->imageFile) {
            $payload['image'] = $this->imageFile->store('banners', 'public');
            $payload['video'] = null; // hapus video lama jika ganti ke image
        }

        if ($this->asset_type === 'video' && $this->videoFile) {
            $payload['video'] = $this->videoFile->store('banners/videos', 'public');
            $payload['image'] = null; // hapus image lama jika ganti ke video
        }

        Banner::updateOrCreate(
            ['id' => $this->editingId],
            $payload
        );

        session()->flash('success', 'Banner berhasil disimpan.');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $banner = Banner::findOrFail($id);

        $this->editingId   = $banner->id;
        $this->title       = $banner->title;
        $this->subtitle    = $banner->subtitle ?? '';
        $this->button_text = $banner->button_text ?? '';
        $this->button_url  = $banner->button_url ?? '';
        $this->is_active   = $banner->is_active;
        $this->sort_order  = $banner->sort_order;
        $this->asset_type  = $banner->asset_type ?? 'image';
    }

    public function delete(int $id): void
    {
        Banner::findOrFail($id)->delete();
        session()->flash('success', 'Banner berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'title', 'subtitle',
            'button_text', 'button_url',
            'imageFile', 'videoFile',
        ]);

        $this->is_active  = true;
        $this->sort_order = 0;
        $this->asset_type = 'image';
    }
};
?>

<div>
    <h1>Kelola Banner Home</h1>

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="admin-card admin-form">
        <h2>{{ $editingId ? 'Edit Banner' : 'Tambah Banner' }}</h2>

        <div class="admin-grid">
            <label>
                Judul
                <input type="text" wire:model="title">
                @error('title') <span class="error-text">{{ $message }}</span> @enderror
            </label>

            <label>
                Subjudul
                <input type="text" wire:model="subtitle">
            </label>

            <label>
                Teks Tombol
                <input type="text" wire:model="button_text" placeholder="Belanja Sekarang">
            </label>

            <label>
                URL Tombol
                <input type="text" wire:model="button_url" placeholder="/products">
            </label>

            {{-- Toggle tipe asset --}}
            <label>
                Tipe Asset
                <select wire:model.live="asset_type">
                    <option value="image">Gambar</option>
                    <option value="video">Video</option>
                </select>
            </label>

            {{-- Kondisional: tampilkan input sesuai tipe --}}
            @if($asset_type === 'image')
                <label>
                    Gambar Banner
                    <small>(JPG/PNG, maks 4 MB)</small>
                    <input type="file" wire:model="imageFile" accept="image/*">
                    @error('imageFile') <span class="error-text">{{ $message }}</span> @enderror
                </label>
            @else
                <label>
                    Video Banner
                    <small>(MP4/WebM, maks 60 MB, durasi &le;30 detik disarankan)</small>
                    <input type="file" wire:model="videoFile" accept="video/mp4,video/webm,video/ogg">
                    @error('videoFile') <span class="error-text">{{ $message }}</span> @enderror
                </label>
            @endif

            <label>
                Status
                <select wire:model="is_active">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </label>
        </div>

        <div class="admin-actions">
            <button class="admin-btn" type="submit">Simpan</button>
            <button class="admin-btn secondary" type="button" wire:click="resetForm">Reset</button>
        </div>
    </form>

    <div class="admin-card">
        <h2>Data Banner</h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Tipe</th>
                    <th>URL</th>
                    <th>Status</th>
                    <th>Urutan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($this->banners as $banner)
                    <tr>
                        <td>{{ $banner->title }}</td>
                        <td>
                            @if(($banner->asset_type ?? 'image') === 'video')
                                Video
                            @else
                                Gambar
                            @endif
                        </td>
                        <td>{{ $banner->button_url }}</td>
                        <td>{{ $banner->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td>{{ $banner->sort_order }}</td>
                        <td>
                            <button class="admin-btn" wire:click="edit({{ $banner->id }})">Edit</button>
                            <button class="admin-btn danger" wire:click="delete({{ $banner->id }})">Hapus</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>