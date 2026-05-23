<?php

use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts.admin')]
#[Title('Admin Kategori - Compify')]
class extends Component {
    use WithFileUploads;

    public ?int $editingId = null;

    public string $parent_id = '';
    public string $name = '';
    public string $description = '';
    public bool $is_active = true;
    public int $sort_order = 0;

    public $imageFile = null;
    public ?string $currentImage = null;

    #[Computed]
    public function categories()
    {
        return Category::with('parent')
            ->orderByRaw('parent_id IS NOT NULL')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function parentOptions()
    {
        return Category::query()
            ->whereNull('parent_id')
            ->when($this->editingId, function ($query) {
                $query->where('id', '!=', $this->editingId);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function save(): void
    {
        $this->validate([
            'parent_id' => ['nullable', 'exists:categories,id'],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'imageFile' => ['nullable', 'image', 'max:2048'],
        ]);

        $slug = Str::slug($this->name);

        if (Category::where('slug', $slug)->where('id', '!=', $this->editingId)->exists()) {
            $slug .= '-' . Str::lower(Str::random(5));
        }

        $payload = [
            'parent_id' => $this->parent_id !== '' ? (int) $this->parent_id : null,
            'name' => $this->name,
            'slug' => $slug,
            'description' => $this->description ?: null,
            'is_active' => (bool) $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->imageFile) {
            if ($this->currentImage && Storage::disk('public')->exists($this->currentImage)) {
                Storage::disk('public')->delete($this->currentImage);
            }

            $payload['image'] = $this->imageFile->store('categories', 'public');
        }

        if ($this->editingId) {
            $category = Category::findOrFail($this->editingId);
            $category->update($payload);
        } else {
            Category::create($payload);
        }

        session()->flash('success', 'Kategori berhasil disimpan.');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $category = Category::findOrFail($id);

        $this->editingId = $category->id;
        $this->parent_id = $category->parent_id ? (string) $category->parent_id : '';
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->is_active = (bool) $category->is_active;
        $this->sort_order = $category->sort_order;
        $this->currentImage = $category->image;
        $this->imageFile = null;
    }

    public function delete(int $id): void
    {
        $category = Category::findOrFail($id);

        if ($category->children()->exists()) {
            session()->flash('success', 'Kategori ini masih punya subkategori. Hapus atau pindahkan subkategori dulu.');
            return;
        }

        if ($category->products()->exists()) {
            session()->flash('success', 'Kategori ini masih punya produk. Pindahkan produk dulu atau nonaktifkan kategori.');
            return;
        }

        if ($category->image && Storage::disk('public')->exists($category->image)) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        session()->flash('success', 'Kategori berhasil dihapus.');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId',
            'parent_id',
            'name',
            'description',
            'imageFile',
            'currentImage',
        ]);

        $this->is_active = true;
        $this->sort_order = 0;
    }
};
?>

<div>
    <h1>Kelola Kategori</h1>

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="admin-card admin-form">
        <h2>{{ $editingId ? 'Edit Kategori' : 'Tambah Kategori' }}</h2>

        <div class="admin-grid">
            <label>
                Parent Kategori
                <select wire:model="parent_id">
                    <option value="">Kategori Utama</option>

                    @foreach($this->parentOptions as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                    @endforeach
                </select>

                @error('parent_id')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </label>

            <label>
                Nama Kategori
                <input type="text" wire:model="name" placeholder="Contoh: Motherboard">

                @error('name')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </label>

            <label>
                Urutan Tampil
                <input type="number" wire:model="sort_order" min="0">

                @error('sort_order')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </label>

            <label>
                Status
                <select wire:model="is_active">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </label>

            <label>
                Gambar Kategori
                <input type="file" wire:model="imageFile" accept="image/*">

                @error('imageFile')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </label>

            <div>
                @if($imageFile)
                    <p>Preview gambar baru:</p>
                    <img src="{{ $imageFile->temporaryUrl() }}" alt="Preview" style="width: 120px; height: 120px; object-fit: cover; border-radius: 12px;">
                @elseif($currentImage)
                    <p>Gambar saat ini:</p>
                    <img src="{{ Storage::url($currentImage) }}" alt="Current Image" style="width: 120px; height: 120px; object-fit: cover; border-radius: 12px;">
                @endif
            </div>
        </div>

        <br>

        <label>
            Deskripsi
            <textarea wire:model="description" rows="4" placeholder="Deskripsi singkat kategori"></textarea>

            @error('description')
                <span class="error-text">{{ $message }}</span>
            @enderror
        </label>

        <div class="admin-actions">
            <button class="admin-btn" type="submit">
                {{ $editingId ? 'Update Kategori' : 'Simpan Kategori' }}
            </button>

            <button class="admin-btn secondary" type="button" wire:click="resetForm">
                Reset
            </button>
        </div>
    </form>

    <div class="admin-card">
        <h2>Data Kategori</h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Parent</th>
                    <th>Status</th>
                    <th>Urutan</th>
                    <th>Gambar</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse($this->categories as $category)
                    <tr>
                        <td>
                            @if($category->parent_id)
                                — {{ $category->name }}
                            @else
                                <strong>{{ $category->name }}</strong>
                            @endif
                        </td>

                        <td>{{ $category->parent?->name ?? 'Kategori Utama' }}</td>
                        <td>{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td>{{ $category->sort_order }}</td>

                        <td>
                            @if($category->image)
                                <img src="{{ Storage::url($category->image) }}" alt="{{ $category->name }}" style="width: 54px; height: 54px; object-fit: cover; border-radius: 10px;">
                            @else
                                -
                            @endif
                        </td>

                        <td>
                            <button class="admin-btn" type="button" wire:click="edit({{ $category->id }})">
                                Edit
                            </button>

                            <button class="admin-btn danger" type="button" wire:click="delete({{ $category->id }})" wire:confirm="Yakin ingin menghapus kategori ini?">
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Belum ada kategori.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>