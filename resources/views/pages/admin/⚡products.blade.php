<?php

use App\Models\Category;
use Illuminate\Support\Str;
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
    public string $name = '';
    public string $description = '';
    public bool $is_active = true;
    public int $sort_order = 0;
    public $imageFile = null;

    #[Computed]
    public function categories()
    {
        return Category::orderBy('sort_order')->latest()->get();
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'imageFile' => ['nullable', 'image', 'max:2048'],
        ]);

        $slug = Str::slug($this->name);

        if (Category::where('slug', $slug)->where('id', '!=', $this->editingId)->exists()) {
            $slug .= '-' . Str::lower(Str::random(5));
        }

        $payload = [
            'name' => $this->name,
            'slug' => $slug,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->imageFile) {
            $payload['image'] = $this->imageFile->store('categories', 'public');
        }

        Category::updateOrCreate(
            ['id' => $this->editingId],
            $payload
        );

        session()->flash('success', 'Kategori berhasil disimpan.');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $category = Category::findOrFail($id);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->is_active = $category->is_active;
        $this->sort_order = $category->sort_order;
    }

    public function delete(int $id): void
    {
        $category = Category::findOrFail($id);

        if ($category->products()->exists()) {
            session()->flash('success', 'Kategori masih memiliki produk, nonaktifkan saja jika tidak ingin ditampilkan.');
            return;
        }

        $category->delete();
        session()->flash('success', 'Kategori berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'imageFile']);
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
                Nama Kategori
                <input type="text" wire:model="name">
                @error('name') <span class="error-text">{{ $message }}</span> @enderror
            </label>

            <label>
                Urutan
                <input type="number" wire:model="sort_order">
            </label>

            <label>
                Gambar
                <input type="file" wire:model="imageFile">
                @error('imageFile') <span class="error-text">{{ $message }}</span> @enderror
            </label>

            <label>
                Status
                <select wire:model="is_active">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </label>
        </div>

        <br>

        <label>
            Deskripsi
            <textarea wire:model="description" rows="4"></textarea>
        </label>

        <div class="admin-actions">
            <button class="admin-btn" type="submit">Simpan</button>
            <button class="admin-btn secondary" type="button" wire:click="resetForm">Reset</button>
        </div>
    </form>

    <div class="admin-card">
        <h2>Data Kategori</h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Status</th>
                    <th>Urutan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($this->categories as $category)
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td>{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td>{{ $category->sort_order }}</td>
                        <td>
                            <button class="admin-btn" wire:click="edit({{ $category->id }})">Edit</button>
                            <button class="admin-btn danger" wire:click="delete({{ $category->id }})">Hapus</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>