<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
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
#[Title('Admin Produk - Compify')]
class extends Component {
    use WithFileUploads;

    public ?int $editingId = null;

    public string $category_id = '';
    public string $brand_id = '';
    public string $name = '';
    public string $sku = '';
    public string $description = '';
    public string $price = '';
    public string $sale_price = '';
    public int $stock = 0;
    public bool $is_featured = false;
    public bool $is_new = false;
    public bool $is_active = true;
    public int $sort_order = 0;

    public $imageFile = null;
    public ?string $currentImage = null;

    #[Computed]
    public function products()
    {
        return Product::with(['category', 'brand'])
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
    public function brands()
    {
        return Brand::active()
            ->orderBy('name')
            ->get();
    }

    public function save(): void
    {
        $this->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($this->editingId),
            ],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_featured' => ['boolean'],
            'is_new' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'imageFile' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($this->sale_price !== '' && (float) $this->sale_price > (float) $this->price) {
            $this->addError('sale_price', 'Harga diskon tidak boleh lebih besar dari harga normal.');
            return;
        }

        $slug = Str::slug($this->name);

        if (Product::where('slug', $slug)->where('id', '!=', $this->editingId)->exists()) {
            $slug .= '-' . Str::lower(Str::random(5));
        }

        $payload = [
            'category_id' => (int) $this->category_id,
            'brand_id' => $this->brand_id !== '' ? (int) $this->brand_id : null,
            'name' => $this->name,
            'slug' => $slug,
            'sku' => $this->sku ?: null,
            'description' => $this->description ?: null,
            'price' => $this->price,
            'sale_price' => $this->sale_price !== '' ? $this->sale_price : null,
            'stock' => $this->stock,
            'is_featured' => (bool) $this->is_featured,
            'is_new' => (bool) $this->is_new,
            'is_active' => (bool) $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->imageFile) {
            if ($this->currentImage && Storage::disk('public')->exists($this->currentImage)) {
                Storage::disk('public')->delete($this->currentImage);
            }

            $payload['image'] = $this->imageFile->store('products', 'public');
        }

        if ($this->editingId) {
            $product = Product::findOrFail($this->editingId);
            $product->update($payload);
        } else {
            Product::create($payload);
        }

        session()->flash('success', 'Produk berhasil disimpan.');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $product = Product::findOrFail($id);

        $this->editingId = $product->id;
        $this->category_id = $product->category_id ? (string) $product->category_id : '';
        $this->brand_id = $product->brand_id ? (string) $product->brand_id : '';
        $this->name = $product->name;
        $this->sku = $product->sku ?? '';
        $this->description = $product->description ?? '';
        $this->price = (string) $product->price;
        $this->sale_price = $product->sale_price ? (string) $product->sale_price : '';
        $this->stock = $product->stock;
        $this->is_featured = (bool) $product->is_featured;
        $this->is_new = (bool) $product->is_new;
        $this->is_active = (bool) $product->is_active;
        $this->sort_order = $product->sort_order;
        $this->currentImage = $product->image;
        $this->imageFile = null;
    }

    public function delete(int $id): void
    {
        $product = Product::findOrFail($id);

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        session()->flash('success', 'Produk berhasil dihapus.');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId',
            'category_id',
            'brand_id',
            'name',
            'sku',
            'description',
            'price',
            'sale_price',
            'imageFile',
            'currentImage',
        ]);

        $this->stock = 0;
        $this->is_featured = false;
        $this->is_new = false;
        $this->is_active = true;
        $this->sort_order = 0;
    }
};
?>

<div>
    <h1>Kelola Produk</h1>

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="admin-card admin-form">
        <h2>{{ $editingId ? 'Edit Produk' : 'Tambah Produk' }}</h2>

        <div class="admin-grid">
            <label>
                Nama Produk
                <input type="text" wire:model="name" placeholder="Contoh: ASUS Prime B760M-A">

                @error('name')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </label>

            <label>
                SKU
                <input type="text" wire:model="sku" placeholder="Contoh: MB-ASUS-B760M">

                @error('sku')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </label>

            <label>
                Kategori
                <select wire:model="category_id">
                    <option value="">Pilih kategori</option>

                    @foreach($this->categories as $category)
                        <option value="{{ $category->id }}">
                            {{ $category->parent_id ? '— ' : '' }}{{ $category->name }}
                        </option>
                    @endforeach
                </select>

                @error('category_id')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </label>

            <label>
                Brand
                <select wire:model="brand_id">
                    <option value="">Tanpa brand</option>

                    @foreach($this->brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>

                @error('brand_id')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </label>

            <label>
                Harga Normal
                <input type="number" wire:model="price" min="0" placeholder="Contoh: 2450000">

                @error('price')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </label>

            <label>
                Harga Diskon
                <input type="number" wire:model="sale_price" min="0" placeholder="Kosongkan jika tidak diskon">

                @error('sale_price')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </label>

            <label>
                Stok
                <input type="number" wire:model="stock" min="0">

                @error('stock')
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
                Produk Unggulan
                <select wire:model="is_featured">
                    <option value="0">Tidak</option>
                    <option value="1">Ya</option>
                </select>
            </label>

            <label>
                Produk Baru
                <select wire:model="is_new">
                    <option value="0">Tidak</option>
                    <option value="1">Ya</option>
                </select>
            </label>

            <label>
                Status Tampil
                <select wire:model="is_active">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </label>

            <label>
                Gambar Produk
                <input type="file" wire:model="imageFile" accept="image/*">

                @error('imageFile')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </label>

            <div>
                @if($imageFile)
                    <p>Preview gambar baru:</p>
                    <img src="{{ $imageFile->temporaryUrl() }}" alt="Preview" style="width: 130px; height: 130px; object-fit: cover; border-radius: 12px;">
                @elseif($currentImage)
                    <p>Gambar saat ini:</p>
                    <img src="{{ Storage::url($currentImage) }}" alt="Current Image" style="width: 130px; height: 130px; object-fit: cover; border-radius: 12px;">
                @endif
            </div>
        </div>

        <br>

        <label>
            Deskripsi Produk
            <textarea wire:model="description" rows="5" placeholder="Tulis deskripsi produk"></textarea>

            @error('description')
                <span class="error-text">{{ $message }}</span>
            @enderror
        </label>

        <div class="admin-actions">
            <button class="admin-btn" type="submit">
                {{ $editingId ? 'Update Produk' : 'Simpan Produk' }}
            </button>

            <button class="admin-btn secondary" type="button" wire:click="resetForm">
                Reset
            </button>
        </div>
    </form>

    <div class="admin-card">
        <h2>Data Produk</h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Gambar</th>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Brand</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Label</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse($this->products as $product)
                    <tr>
                        <td>
                            @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" style="width: 58px; height: 58px; object-fit: cover; border-radius: 10px;">
                            @else
                                -
                            @endif
                        </td>

                        <td>
                            <strong>{{ $product->name }}</strong>
                            <br>
                            <small>{{ $product->sku ?? 'Tanpa SKU' }}</small>
                        </td>

                        <td>{{ $product->category?->name ?? '-' }}</td>
                        <td>{{ $product->brand?->name ?? '-' }}</td>

                        <td>
                            @if($product->sale_price)
                                <small style="text-decoration: line-through;">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                </small>
                                <br>
                                <strong>
                                    Rp {{ number_format($product->sale_price, 0, ',', '.') }}
                                </strong>
                            @else
                                <strong>
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                </strong>
                            @endif
                        </td>

                        <td>{{ $product->stock }}</td>

                        <td>
                            @if($product->is_new)
                                <span>New</span>
                                <br>
                            @endif

                            @if($product->is_featured)
                                <span>Unggulan</span>
                            @endif

                            @if(! $product->is_new && ! $product->is_featured)
                                -
                            @endif
                        </td>

                        <td>{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</td>

                        <td>
                            <button class="admin-btn" type="button" wire:click="edit({{ $product->id }})">
                                Edit
                            </button>

                            <button class="admin-btn danger" type="button" wire:click="delete({{ $product->id }})" wire:confirm="Yakin ingin menghapus produk ini?">
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">Belum ada produk.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>