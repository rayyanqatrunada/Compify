<?php

use App\Models\HomeSection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new
#[Layout('components.layouts.admin')]
#[Title('Split Banners - Admin Compify')]
class extends Component {
    use WithFileUploads;
    use WithPagination;

    public int $perPage = 10;

    public ?int $editingId = null;

    public string $title = '';
    public string $subtitle = '';
    public string $description = '';
    public string $button_text = '';
    public string $button_url = '';
    public string $image_position = 'right';

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
        return HomeSection::query()
            ->where('section_type', 'story')
            ->where('display_style', 'split')
            ->orderBy('sort_order')
            ->latest()
            ->paginate($this->perPage);
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'image_position' => ['required', 'in:left,right'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
            'imageFile' => ['nullable', 'image', 'max:4096'],
        ]);

        $payload = [
            'section_type' => 'story',
            'display_style' => 'split',
            'category_id' => null,
            'product_id' => null,
            'title' => $this->title ?: null,
            'subtitle' => $this->subtitle ?: null,
            'description' => $this->description ?: null,
            'button_text' => $this->button_text ?: null,
            'button_url' => $this->button_url ?: null,
            'image_position' => $this->image_position,
            'auto_slide' => false,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->imageFile) {
            if ($this->currentImage && Storage::disk('public')->exists($this->currentImage)) {
                Storage::disk('public')->delete($this->currentImage);
            }

            $payload['image'] = $this->imageFile->store('home-sections', 'public');
        }

        if ($this->editingId) {
            HomeSection::findOrFail($this->editingId)->update($payload);
        } else {
            HomeSection::create($payload);
        }

        session()->flash('success', 'Split banner berhasil disimpan.');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $section = HomeSection::findOrFail($id);

        $this->editingId = $section->id;
        $this->title = $section->title ?? '';
        $this->subtitle = $section->subtitle ?? '';
        $this->description = $section->description ?? '';
        $this->button_text = $section->button_text ?? '';
        $this->button_url = $section->button_url ?? '';
        $this->image_position = $section->image_position ?? 'right';
        $this->is_active = (bool) $section->is_active;
        $this->sort_order = $section->sort_order ?? 0;
        $this->currentImage = $section->image;
        $this->imageFile = null;
    }

    public function delete(int $id): void
    {
        $section = HomeSection::findOrFail($id);

        if ($section->image && Storage::disk('public')->exists($section->image)) {
            Storage::disk('public')->delete($section->image);
        }

        $section->delete();

        session()->flash('success', 'Split banner berhasil dihapus.');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editingId = null;

        $this->title = '';
        $this->subtitle = '';
        $this->description = '';
        $this->button_text = '';
        $this->button_url = '';
        $this->image_position = 'right';

        $this->is_active = true;
        $this->sort_order = 0;

        $this->imageFile = null;
        $this->currentImage = null;

        $this->resetValidation();
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <h2>Split Banners</h2>
        <p>Mengatur preview kanan-kiri di homepage.</p>
    </div>

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="admin-panel-v2 admin-form">
        <h2>{{ $editingId ? 'Edit Split Banner' : 'Tambah Split Banner' }}</h2>

        <div class="admin-grid">
            <label>
                Judul
                <input type="text" wire:model="title" placeholder="Contoh: Upgrade Setup Gaming">
            </label>

            <label>
                Subtitle
                <input type="text" wire:model="subtitle" placeholder="Opsional">
            </label>

            <label>
                Button Text
                <input type="text" wire:model="button_text" placeholder="Contoh: Learn More">
            </label>

            <label>
                Button URL
                <input type="text" wire:model="button_url" placeholder="/products">
            </label>

            <label>
                Urutan
                <input type="number" wire:model="sort_order" min="0">
            </label>

            <label>
                Status
                <select wire:model="is_active">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </label>

            <label>
                Posisi Gambar
                <select wire:model="image_position">
                    <option value="right">Gambar Kanan</option>
                    <option value="left">Gambar Kiri</option>
                </select>
            </label>

            <label>
                Gambar Banner
                <input type="file" wire:model="imageFile" accept="image/*">
            </label>
        </div>

        <br>

        <label>
            Deskripsi
            <textarea wire:model="description" rows="4" placeholder="Deskripsi singkat untuk split banner"></textarea>
        </label>

        <div class="home-section-preview-grid">
            <div>
                <strong>Preview Gambar</strong>

                @if($imageFile)
                    <img src="{{ $imageFile->temporaryUrl() }}" alt="Preview">
                @elseif($currentImage)
                    <img src="{{ Storage::url($currentImage) }}" alt="Current Image">
                @else
                    <span>Belum ada gambar</span>
                @endif
            </div>
        </div>

        <div class="admin-actions">
            <button class="admin-btn" type="submit">
                {{ $editingId ? 'Update Split Banner' : 'Simpan Split Banner' }}
            </button>

            <button class="admin-btn secondary" type="button" wire:click="resetForm">
                Reset
            </button>
        </div>
    </form>

    <div class="admin-panel-v2">
        <div class="admin-table-head">
            <h2>Data Split Banners</h2>

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
                    <th>Posisi</th>
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
                                <img src="{{ Storage::url($section->image) }}" class="admin-table-thumb" alt="Split Banner">
                            @else
                                -
                            @endif
                        </td>

                        <td>{{ $section->title ?? '-' }}</td>

                        <td>
                            {{ $section->image_position === 'left' ? 'Gambar Kiri' : 'Gambar Kanan' }}
                        </td>

                        <td>{{ $section->is_active ? 'Aktif' : 'Nonaktif' }}</td>

                        <td>
                            <button class="admin-btn" type="button" wire:click="edit({{ $section->id }})">
                                Edit
                            </button>

                            <button
                                class="admin-btn danger"
                                type="button"
                                wire:click="delete({{ $section->id }})"
                                wire:confirm="Yakin hapus split banner ini?"
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Belum ada split banner.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="admin-pagination">
            {{ $this->sections->links() }}
        </div>
    </div>
</div>