<?php

use App\Models\AboutSection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new
#[Layout('components.layouts.admin')]
#[Title('About Images - Admin Compify')]
class extends Component {
    use WithFileUploads;
    use WithPagination;

    public int $perPage = 10;
    public string $activeTab = 'hero';

    // Shared form fields
    public ?int $editingId = null;
    public string $title = '';
    public string $subtitle = '';
    public string $button_text = '';
    public string $button_url = '';
    public bool $is_active = true;
    public int $sort_order = 0;
    public $imageFile = null;
    public ?string $currentImage = null;

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetForm();
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    private function activeType(): string
    {
        return $this->activeTab === 'hero'
            ? AboutSection::TYPE_HERO
            : AboutSection::TYPE_BANNER;
    }

    #[Computed]
    public function sections()
    {
        return AboutSection::type($this->activeType())
            ->orderBy('sort_order')
            ->latest()
            ->paginate($this->perPage);
    }

    public function save(): void
    {
        $rules = [
            'title'      => ['required', 'string', 'max:255'],
            'subtitle'   => ['nullable', 'string', 'max:255'],
            'is_active'  => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'imageFile'  => ['nullable', 'image', 'max:4096'],
        ];

        if ($this->activeTab === 'banner') {
            $rules['button_text'] = ['nullable', 'string', 'max:100'];
            $rules['button_url']  = ['nullable', 'string', 'max:255'];
        }

        $this->validate($rules);

        $payload = [
            'section_type' => $this->activeType(),
            'title'        => $this->title,
            'subtitle'     => $this->subtitle ?: null,
            'is_active'    => $this->is_active,
            'sort_order'   => $this->sort_order,
        ];

        if ($this->activeTab === 'banner') {
            $payload['button_text'] = $this->button_text ?: null;
            $payload['button_url']  = $this->button_url ?: null;
        }

        if ($this->imageFile) {
            if ($this->currentImage && Storage::disk('public')->exists($this->currentImage)) {
                Storage::disk('public')->delete($this->currentImage);
            }
            $payload['image'] = $this->imageFile->store('about-sections', 'public');
        }

        if ($this->editingId) {
            AboutSection::findOrFail($this->editingId)->update($payload);
        } else {
            AboutSection::create($payload);
        }

        session()->flash('success', 'Data berhasil disimpan.');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $section = AboutSection::findOrFail($id);

        $this->editingId    = $section->id;
        $this->title        = $section->title ?? '';
        $this->subtitle     = $section->subtitle ?? '';
        $this->button_text  = $section->button_text ?? '';
        $this->button_url   = $section->button_url ?? '';
        $this->is_active    = (bool) $section->is_active;
        $this->sort_order   = $section->sort_order ?? 0;
        $this->currentImage = $section->image;
        $this->imageFile    = null;
    }

    public function delete(int $id): void
    {
        $section = AboutSection::findOrFail($id);

        if ($section->image && Storage::disk('public')->exists($section->image)) {
            Storage::disk('public')->delete($section->image);
        }

        $section->delete();

        session()->flash('success', 'Data berhasil dihapus.');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editingId    = null;
        $this->title        = '';
        $this->subtitle     = '';
        $this->button_text  = '';
        $this->button_url   = '';
        $this->is_active    = true;
        $this->sort_order   = 0;
        $this->imageFile    = null;
        $this->currentImage = null;
        $this->resetValidation();
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <h2>About Images Manager</h2>
        <p>Mengatur Hero dan Banner pada halaman About.</p>
    </div>

    {{-- Tab Switch --}}
    <div class="admin-tabs-v2" style="display:flex;gap:8px;margin-bottom:24px;">
        <button
            class="admin-btn {{ $activeTab === 'hero' ? '' : 'secondary' }}"
            type="button"
            wire:click="switchTab('hero')"
        >Hero</button>

        <button
            class="admin-btn {{ $activeTab === 'banner' ? '' : 'secondary' }}"
            type="button"
            wire:click="switchTab('banner')"
        >Banner</button>
    </div>

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="admin-panel-v2 admin-form">
        <h2>{{ $editingId ? 'Edit' : 'Tambah' }} About {{ ucfirst($activeTab) }}</h2>

        <div class="admin-grid">
            <label>
                Judul
                <input type="text" wire:model="title" placeholder="{{ $activeTab === 'hero' ? 'About Us' : 'Jadilah Bagian dari Compify' }}">
                @error('title') <span class="error-text">{{ $message }}</span> @enderror
            </label>

            @if($activeTab === 'banner')
                <label>
                    Subjudul
                    <input type="text" wire:model="subtitle" placeholder="Subjudul banner">
                    @error('subtitle') <span class="error-text">{{ $message }}</span> @enderror
                </label>

                <label>
                    Teks Tombol
                    <input type="text" wire:model="button_text" placeholder="Mulai Belanja">
                    @error('button_text') <span class="error-text">{{ $message }}</span> @enderror
                </label>

                <label>
                    URL Tombol
                    <input type="text" wire:model="button_url" placeholder="/products">
                    @error('button_url') <span class="error-text">{{ $message }}</span> @enderror
                </label>
            @endif

            <label>
                Urutan
                <input type="number" wire:model="sort_order" min="0">
                @error('sort_order') <span class="error-text">{{ $message }}</span> @enderror
            </label>

            <label>
                Status
                <select wire:model="is_active">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </label>

            <label>
                Gambar Background
                <input type="file" wire:model="imageFile" accept="image/*">
                @error('imageFile') <span class="error-text">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="home-section-preview-grid">
            <div>
                <strong>Preview Gambar</strong>

                @if($imageFile)
                    <img src="{{ $imageFile->temporaryUrl() }}" alt="Preview">
                @elseif($currentImage)
                    <img src="{{ Storage::url($currentImage) }}" alt="Current image">
                @else
                    <span>Belum ada gambar</span>
                @endif
            </div>
        </div>

        <div class="admin-actions">
            <button class="admin-btn" type="submit">{{ $editingId ? 'Update' : 'Simpan' }}</button>
            <button class="admin-btn secondary" type="button" wire:click="resetForm">Reset</button>
        </div>
    </form>

    <div class="admin-panel-v2">
        <div class="admin-table-head">
            <h2>Data About {{ ucfirst($activeTab) }}</h2>

            <select wire:model.live="perPage">
                <option value="10">10 data</option>
                <option value="20">20 data</option>
                <option value="50">50 data</option>
            </select>
        </div>

        <table class="admin-table-v2">
            <thead>
                <tr>
                    <th>Urutan</th>
                    <th>Gambar</th>
                    <th>Judul</th>
                    @if($activeTab === 'banner')
                        <th>Subjudul</th>
                        <th>Tombol</th>
                    @endif
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->sections as $section)
                    <tr>
                        <td>{{ $section->sort_order }}</td>
                        <td>
                            @if($section->image)
                                <img src="{{ Storage::url($section->image) }}" class="admin-table-thumb" alt="Image">
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $section->title ?? '-' }}</td>
                        @if($activeTab === 'banner')
                            <td>{{ $section->subtitle ?? '-' }}</td>
                            <td>{{ $section->button_text ?: '-' }}</td>
                        @endif
                        <td>{{ $section->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td>
                            <button class="admin-btn" type="button" wire:click="edit({{ $section->id }})">Edit</button>
                            <button class="admin-btn danger" type="button" wire:click="delete({{ $section->id }})" wire:confirm="Yakin hapus data ini?">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $activeTab === 'banner' ? 7 : 5 }}">Belum ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="admin-pagination">
            {{ $this->sections->links() }}
        </div>
    </div>
</div>