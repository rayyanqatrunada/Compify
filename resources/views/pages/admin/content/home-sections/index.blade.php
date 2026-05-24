<?php

use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts.admin')]
#[Title('Home Sections - Admin Compify')]
class extends Component {
    use WithFileUploads;

    public ?int $editingId = null;

    public string $section_type = 'category_products';
    public string $category_id = '';
    public string $product_id = '';

    public string $title = '';
    public string $subtitle = '';
    public string $description = '';

    public string $button_text = '';
    public string $button_url = '';

    public string $image_position = 'right';
    public bool $auto_slide = false;
    public bool $is_active = true;
    public int $sort_order = 0;

    public $imageFile = null;
    public $image2File = null;
    public $image3File = null;

    public ?string $currentImage = null;
    public ?string $currentImage2 = null;
    public ?string $currentImage3 = null;

    #[Computed]
    public function sections()
    {
        return HomeSection::with(['category', 'product'])
            ->orderBy('sort_order')
            ->latest()
            ->get();
    }

    #[Computed]
    public function categories()
    {
        return Category::active()
            ->orderByRaw('parent_id IS NOT NULL')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function products()
    {
        return Product::active()
            ->orderBy('name')
            ->get();
    }

    public function save(): void
    {
        $data = $this->validate([
            'section_type' => ['required', Rule::in(['category_products', 'story', 'gallery'])],
            'category_id' => ['nullable', 'exists:categories,id'],
            'product_id' => ['nullable', 'exists:products,id'],

            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'button_text' => ['nullable', 'string', 'max:255'],
            'button_url' => ['nullable', 'string', 'max:255'],

            'image_position' => ['required', Rule::in(['left', 'right'])],
            'auto_slide' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],

            'imageFile' => ['nullable', 'image', 'max:4096'],
            'image2File' => ['nullable', 'image', 'max:4096'],
            'image3File' => ['nullable', 'image', 'max:4096'],
        ]);

        $payload = [
            'section_type' => $this->section_type,
            'category_id' => $this->category_id !== '' ? (int) $this->category_id : null,
            'product_id' => $this->product_id !== '' ? (int) $this->product_id : null,
            'title' => $this->title ?: null,
            'subtitle' => $this->subtitle ?: null,
            'description' => $this->description ?: null,
            'button_text' => $this->button_text ?: null,
            'button_url' => $this->button_url ?: null,
            'image_position' => $this->image_position,
            'auto_slide' => $this->auto_slide,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->imageFile) {
            if ($this->currentImage && Storage::disk('public')->exists($this->currentImage)) {
                Storage::disk('public')->delete($this->currentImage);
            }

            $payload['image'] = $this->imageFile->store('home-sections', 'public');
        }

        if ($this->image2File) {
            if ($this->currentImage2 && Storage::disk('public')->exists($this->currentImage2)) {
                Storage::disk('public')->delete($this->currentImage2);
            }

            $payload['image_2'] = $this->image2File->store('home-sections', 'public');
        }

        if ($this->image3File) {
            if ($this->currentImage3 && Storage::disk('public')->exists($this->currentImage3)) {
                Storage::disk('public')->delete($this->currentImage3);
            }

            $payload['image_3'] = $this->image3File->store('home-sections', 'public');
        }

        if ($this->editingId) {
            HomeSection::findOrFail($this->editingId)->update($payload);
        } else {
            HomeSection::create($payload);
        }

        session()->flash('success', 'Home section berhasil disimpan.');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $section = HomeSection::findOrFail($id);

        $this->editingId = $section->id;
        $this->section_type = $section->section_type;
        $this->category_id = $section->category_id ? (string) $section->category_id : '';
        $this->product_id = $section->product_id ? (string) $section->product_id : '';

        $this->title = $section->title ?? '';
        $this->subtitle = $section->subtitle ?? '';
        $this->description = $section->description ?? '';

        $this->button_text = $section->button_text ?? '';
        $this->button_url = $section->button_url ?? '';

        $this->image_position = $section->image_position;
        $this->auto_slide = (bool) $section->auto_slide;
        $this->is_active = (bool) $section->is_active;
        $this->sort_order = $section->sort_order;

        $this->currentImage = $section->image;
        $this->currentImage2 = $section->image_2;
        $this->currentImage3 = $section->image_3;

        $this->imageFile = null;
        $this->image2File = null;
        $this->image3File = null;
    }

    public function delete(int $id): void
    {
        $section = HomeSection::findOrFail($id);

        foreach ([$section->image, $section->image_2, $section->image_3] as $image) {
            if ($image && Storage::disk('public')->exists($image)) {
                Storage::disk('public')->delete($image);
            }
        }

        $section->delete();

        session()->flash('success', 'Home section berhasil dihapus.');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId',
            'category_id',
            'product_id',
            'title',
            'subtitle',
            'description',
            'button_text',
            'button_url',
            'imageFile',
            'image2File',
            'image3File',
            'currentImage',
            'currentImage2',
            'currentImage3',
        ]);

        $this->section_type = 'category_products';
        $this->image_position = 'right';
        $this->auto_slide = false;
        $this->is_active = true;
        $this->sort_order = 0;
    }
};
?>

<div>
    <div class="admin-page-head">
        <div>
            <p>Content</p>
            <h2>Home Sections</h2>
        </div>
    </div>

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="admin-card admin-form">
        <h2>{{ $editingId ? 'Edit Home Section' : 'Tambah Home Section' }}</h2>

        <div class="admin-grid">
            <label>
                Tipe Section
                <select wire:model.live="section_type">
                    <option value="category_products">Display Produk Kategori</option>
                    <option value="story">Deskripsi Produk Custom</option>
                    <option value="gallery">Gallery 1 Besar + 2 Kecil</option>
                </select>
            </label>

            <label>
                Kategori untuk Display Produk
                <select wire:model="category_id">
                    <option value="">Pilih kategori</option>
                    @foreach($this->categories as $category)
                        <option value="{{ $category->id }}">
                            {{ $category->parent_id ? '— ' : '' }}{{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label>
                Produk Utama
                <select wire:model="product_id">
                    <option value="">Pilih produk</option>
                    @foreach($this->products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                Urutan
                <input type="number" wire:model="sort_order" min="0">
            </label>

            <label>
                Judul
                <input type="text" wire:model="title" placeholder="Contoh: ONIX TOCATA XM2">
            </label>

            <label>
                Subtitle
                <input type="text" wire:model="subtitle" placeholder="Opsional">
            </label>

            <label>
                Button Text
                <input type="text" wire:model="button_text" placeholder="Contoh: Order Now">
            </label>

            <label>
                Button URL
                <input type="text" wire:model="button_url" placeholder="/products atau URL lain">
            </label>

            <label>
                Posisi Gambar
                <select wire:model="image_position">
                    <option value="right">Gambar Kanan</option>
                    <option value="left">Gambar Kiri</option>
                </select>
            </label>

            <label>
                Auto Slide
                <select wire:model="auto_slide">
                    <option value="0">Tidak</option>
                    <option value="1">Ya, khusus gallery</option>
                </select>
            </label>

            <label>
                Status
                <select wire:model="is_active">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </label>

            <label>
                Gambar 1
                <input type="file" wire:model="imageFile" accept="image/*">
            </label>

            <label>
                Gambar 2
                <input type="file" wire:model="image2File" accept="image/*">
            </label>

            <label>
                Gambar 3
                <input type="file" wire:model="image3File" accept="image/*">
            </label>
        </div>

        <br>

        <label>
            Deskripsi
            <textarea wire:model="description" rows="5" placeholder="Tulis deskripsi custom section"></textarea>
        </label>

        <div class="home-section-preview-grid">
            @foreach([
                'Gambar 1' => [$imageFile, $currentImage],
                'Gambar 2' => [$image2File, $currentImage2],
                'Gambar 3' => [$image3File, $currentImage3],
            ] as $label => [$file, $current])
                <div>
                    <strong>{{ $label }}</strong>

                    @if($file)
                        <img src="{{ $file->temporaryUrl() }}" alt="Preview">
                    @elseif($current)
                        <img src="{{ Storage::url($current) }}" alt="Current image">
                    @else
                        <span>Belum ada gambar</span>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="admin-actions">
            <button class="admin-btn" type="submit">
                {{ $editingId ? 'Update Section' : 'Simpan Section' }}
            </button>

            <button class="admin-btn secondary" type="button" wire:click="resetForm">
                Reset
            </button>
        </div>
    </form>

    <div class="admin-card">
        <h2>Data Home Sections</h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Urutan</th>
                    <th>Tipe</th>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Produk</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse($this->sections as $section)
                    <tr>
                        <td>{{ $section->sort_order }}</td>
                        <td>{{ $section->section_type }}</td>
                        <td>{{ $section->title ?? '-' }}</td>
                        <td>{{ $section->category?->name ?? '-' }}</td>
                        <td>{{ $section->product?->name ?? '-' }}</td>
                        <td>{{ $section->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td>
                            <button class="admin-btn" type="button" wire:click="edit({{ $section->id }})">
                                Edit
                            </button>

                            <button class="admin-btn danger" type="button" wire:click="delete({{ $section->id }})" wire:confirm="Yakin hapus section ini?">
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Belum ada home section.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>