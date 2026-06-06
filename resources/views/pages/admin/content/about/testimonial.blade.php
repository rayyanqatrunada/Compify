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
#[Title('About Testimonial - Admin Compify')]
class extends Component {
    use WithFileUploads;
    use WithPagination;

    public int $perPage = 10;

    public ?int $editingId = null;
    public string $title = '';
    public string $description = '';
    public int $rating = 5;
    public bool $is_active = true;
    public int $sort_order = 0;

    public $imageFile = null;
    public ?string $currentImage = null;

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function sections()
    {
        return AboutSection::type(AboutSection::TYPE_TESTIMONIAL)
            ->orderBy('sort_order')
            ->latest()
            ->paginate($this->perPage);
    }

    public function save(): void
    {
        $this->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'rating'      => ['required', 'integer', 'min:1', 'max:5'],
            'is_active'   => ['boolean'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'imageFile'   => ['nullable', 'image', 'max:2048'],
        ]);

        $payload = [
            'section_type' => AboutSection::TYPE_TESTIMONIAL,
            'title'        => $this->title,
            'description'  => $this->description,
            'rating'       => $this->rating,
            'is_active'    => $this->is_active,
            'sort_order'   => $this->sort_order,
        ];

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

        session()->flash('success', 'About testimonial berhasil disimpan.');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $section = AboutSection::findOrFail($id);

        $this->editingId    = $section->id;
        $this->title        = $section->title ?? '';
        $this->description  = $section->description ?? '';
        $this->rating       = $section->rating ?? 5;
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

        session()->flash('success', 'About testimonial berhasil dihapus.');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editingId    = null;
        $this->title        = '';
        $this->description  = '';
        $this->rating       = 5;
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
        <h2>About Testimonial Manager</h2>
        <p>Mengatur testimoni pelanggan pada halaman About.</p>
    </div>

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="admin-panel-v2 admin-form">
        <h2>{{ $editingId ? 'Edit About Testimonial' : 'Tambah About Testimonial' }}</h2>

        <div class="admin-grid">
            <label>
                Nama Pelanggan
                <input type="text" wire:model="title" placeholder="Budi Santoso">
                @error('title') <span class="error-text">{{ $message }}</span> @enderror
            </label>

            <label>
                Rating (1–5)
                <select wire:model="rating">
                    @foreach(range(1, 5) as $star)
                        <option value="{{ $star }}">{{ $star }} Bintang</option>
                    @endforeach
                </select>
                @error('rating') <span class="error-text">{{ $message }}</span> @enderror
            </label>

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
                Foto Avatar
                <input type="file" wire:model="imageFile" accept="image/*">
                @error('imageFile') <span class="error-text">{{ $message }}</span> @enderror
            </label>
        </div>

        <label>
            Teks Testimoni
            <textarea wire:model="description" rows="4" placeholder="Tulis isi testimoni pelanggan..."></textarea>
            @error('description') <span class="error-text">{{ $message }}</span> @enderror
        </label>

        <div class="home-section-preview-grid">
            <div>
                <strong>Preview Avatar</strong>

                @if($imageFile)
                    <img src="{{ $imageFile->temporaryUrl() }}" alt="Preview">
                @elseif($currentImage)
                    <img src="{{ Storage::url($currentImage) }}" alt="Current avatar">
                @else
                    <span>Belum ada foto</span>
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
            <h2>Data About Testimonial</h2>

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
                    <th>Avatar</th>
                    <th>Nama</th>
                    <th>Rating</th>
                    <th>Testimoni</th>
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
                                <img src="{{ Storage::url($section->image) }}" class="admin-table-thumb" alt="Avatar">
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $section->title ?? '-' }}</td>
                        <td>{{ $section->rating ?? '-' }} ★</td>
                        <td>{{ str($section->description)->limit(80) }}</td>
                        <td>{{ $section->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td>
                            <button class="admin-btn" type="button" wire:click="edit({{ $section->id }})">Edit</button>
                            <button class="admin-btn danger" type="button" wire:click="delete({{ $section->id }})" wire:confirm="Yakin hapus testimoni ini?">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Belum ada about testimonial.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="admin-pagination">
            {{ $this->sections->links() }}
        </div>
    </div>
</div>