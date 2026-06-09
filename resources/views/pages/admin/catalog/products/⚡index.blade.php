<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\CategoryTree;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new
#[Layout('components.layouts.admin')]
#[Title('Admin Produk - Compify')]
class extends Component {
    use WithFileUploads, WithPagination;

    public ?int $editingId = null;

    public string $category_id = '';
    public string $brand_id = '';
    public string $name = '';
    public string $sku = '';
    public string $description = '';
    public string $price = '';
    public string $sale_price = '';
    public string $sale_starts_at = '';
    public string $sale_ends_at = '';

    public int $stock = 0;
    public int $weight_gram = 1000;
    public string $length_cm = '';
    public string $width_cm = '';
    public string $height_cm = '';
    public bool $is_featured = false;
    public bool $is_new = false;
    public bool $is_active = true;
    public int $sort_order = 0;

    public $imageFile = null;
    public ?string $currentImage = null;

    public int $perPage = 10;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'category')]
    public string $categoryFilter = '';

    public bool $showExportModal = false;
    public bool $showImportModal = false;

    public string $formTab = 'required';

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function products()
    {
        $query = Product::with(['category.parent', 'brand']);

        if (filled($this->categoryFilter)) {
            $category = Category::find((int) $this->categoryFilter);
            $categoryIds = CategoryTree::ids($category);

            if (! empty($categoryIds)) {
                $query->whereIn('category_id', $categoryIds);
            }
        }

        if (filled($this->search)) {
            $keyword = '%' . trim($this->search) . '%';

            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', $keyword)
                    ->orWhere('sku', 'like', $keyword)
                    ->orWhere('slug', 'like', $keyword)
                    ->orWhereHas('brand', function ($brand) use ($keyword) {
                        $brand->where('name', 'like', $keyword)
                            ->orWhere('slug', 'like', $keyword);
                    })
                    ->orWhereHas('category', function ($category) use ($keyword) {
                        $category->where('name', 'like', $keyword)
                            ->orWhere('slug', 'like', $keyword)
                            ->orWhereHas('parent', function ($parent) use ($keyword) {
                                $parent->where('name', 'like', $keyword)
                                    ->orWhere('slug', 'like', $keyword);
                            });
                    });
            });
        }

        return $query
            ->orderBy('sort_order')
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed]
    public function categories()
    {
        return Category::query()
            ->where('is_active', true)
            ->orderByRaw('parent_id IS NOT NULL')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function brands()
    {
        return Brand::query()
            ->where('is_active', true)
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
            'sale_starts_at' => ['nullable', 'date'],
            'sale_ends_at' => ['nullable', 'date', 'after_or_equal:sale_starts_at'],
            'stock' => ['required', 'integer', 'min:0'],
            'weight_gram' => ['required', 'integer', 'min:1', 'max:200000'],
            'length_cm' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'width_cm' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'height_cm' => ['nullable', 'integer', 'min:1', 'max:1000'],
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
            'sale_starts_at' => $this->sale_starts_at ?: null,
            'sale_ends_at' => $this->sale_ends_at ?: null,
            'stock' => $this->stock,
            'weight_gram' => $this->weight_gram,
            'length_cm' => $this->length_cm !== '' ? (int) $this->length_cm : null,
            'width_cm' => $this->width_cm !== '' ? (int) $this->width_cm : null,
            'height_cm' => $this->height_cm !== '' ? (int) $this->height_cm : null,
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
            Product::findOrFail($this->editingId)->update($payload);
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

        $this->sale_starts_at = $product->sale_starts_at
            ? $product->sale_starts_at->format('Y-m-d\TH:i')
            : '';

        $this->sale_ends_at = $product->sale_ends_at
            ? $product->sale_ends_at->format('Y-m-d\TH:i')
            : '';

        $this->stock = $product->stock;
        $this->weight_gram = $product->weight_gram ?: 1000;
        $this->length_cm = $product->length_cm ? (string) $product->length_cm : '';
        $this->width_cm = $product->width_cm ? (string) $product->width_cm : '';
        $this->height_cm = $product->height_cm ? (string) $product->height_cm : '';
        $this->is_featured = (bool) $product->is_featured;
        $this->is_new = (bool) $product->is_new;
        $this->is_active = (bool) $product->is_active;
        $this->sort_order = $product->sort_order ?? 0;
        $this->currentImage = $product->image;
        $this->imageFile = null;

        $this->resetValidation();
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
        $this->editingId = null;

        $this->category_id = '';
        $this->brand_id = '';
        $this->name = '';
        $this->sku = '';
        $this->description = '';
        $this->price = '';
        $this->sale_price = '';
        $this->sale_starts_at = '';
        $this->sale_ends_at = '';

        $this->stock = 0;
        $this->weight_gram = 1000;
        $this->length_cm = '';
        $this->width_cm = '';
        $this->height_cm = '';
        $this->is_featured = false;
        $this->is_new = false;
        $this->is_active = true;
        $this->sort_order = 0;

        $this->imageFile = null;
        $this->currentImage = null;
        $this->formTab = 'required';

        $this->resetValidation();
    }

    public function openExportModal(): void
    {
        $this->showExportModal = true;
    }

    public function closeExportModal(): void
    {
        $this->showExportModal = false;
    }

    public function openImportModal(): void
    {
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <h2>Kelola Produk</h2>
        <p>Tambah, edit, export, import, dan atur produk yang tampil di shop.</p>
    </div>

    @if(session('success'))
        <div class="flash-success">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="admin-panel-v2 admin-form admin-product-form-v3">
        <div class="admin-product-form-head-v3">
            <div>
                <h2>{{ $editingId ? 'Edit Produk' : 'Tambah Produk' }}</h2>
            </div>

            <span>{{ $editingId ? 'Mode Edit' : 'Produk Baru' }}</span>
        </div>

        <div class="admin-product-tab-switcher-v3" role="tablist" aria-label="Bagian form produk">
            <button
                type="button"
                wire:click="$set('formTab', 'required')"
                @class([
                    'admin-product-tab-btn-v3',
                    'is-active' => $formTab === 'required',
                ])
            >
                <span>Data Utama</span>
                <small>5 field</small>
            </button>

            <button
                type="button"
                wire:click="$set('formTab', 'optional')"
                @class([
                    'admin-product-tab-btn-v3',
                    'is-active' => $formTab === 'optional',
                ])
            >
                <span>Data Opsional</span>
                <small>Tambahan</small>
            </button>
        </div>

        @if($formTab === 'required')
            <section class="admin-product-form-section-v3">
                <div class="admin-product-section-title-v3">
                    <div>
                        <strong>Data Utama</strong>
                    </div>
                </div>

                <div class="admin-grid admin-product-required-grid-v3">
                    <label>
                        Nama Produk
                        <input type="text" wire:model="name" placeholder="Contoh: ASUS Prime B760M-A">
                        @error('name') <span class="error-text">{{ $message }}</span> @enderror
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
                        @error('category_id') <span class="error-text">{{ $message }}</span> @enderror
                    </label>

                    <label>
                        Harga Normal
                        <input type="number" wire:model="price" min="0" placeholder="Contoh: 2450000">
                        @error('price') <span class="error-text">{{ $message }}</span> @enderror
                    </label>

                    <label>
                        Stok
                        <input type="number" wire:model="stock" min="0">
                        @error('stock') <span class="error-text">{{ $message }}</span> @enderror
                    </label>

                    <label>
                        Berat Produk (gram)
                        <input type="number" wire:model="weight_gram" min="1" placeholder="Contoh: 1000">
                        @error('weight_gram') <span class="error-text">{{ $message }}</span> @enderror
                    </label>
                </div>
            </section>
        @else
            <section class="admin-product-form-section-v3 admin-product-form-section-v3--optional">
                <div class="admin-product-section-title-v3">
                    <div>
                        <strong>Data Opsional</strong>
                        <small>SKU, brand, promo, dimensi, gambar, status, dan deskripsi.</small>
                    </div>
                </div>

                <div class="admin-product-options-body-v3">
                    <div class="admin-grid">
                        <label>
                            SKU
                            <input type="text" wire:model="sku" placeholder="Contoh: MB-ASUS-B760M">
                            @error('sku') <span class="error-text">{{ $message }}</span> @enderror
                        </label>

                        <label>
                            Brand
                            <select wire:model="brand_id">
                                <option value="">Tanpa brand</option>

                                @foreach($this->brands as $brand)
                                    <option value="{{ $brand->id }}">
                                        {{ $brand->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('brand_id') <span class="error-text">{{ $message }}</span> @enderror
                        </label>

                        <label>
                            Harga Diskon
                            <input type="number" wire:model="sale_price" min="0" placeholder="Kosongkan jika tidak diskon">
                            @error('sale_price') <span class="error-text">{{ $message }}</span> @enderror
                        </label>

                        <label>
                            Promo Mulai
                            <input type="datetime-local" wire:model="sale_starts_at">
                            @error('sale_starts_at') <span class="error-text">{{ $message }}</span> @enderror
                        </label>

                        <label>
                            Promo Selesai
                            <input type="datetime-local" wire:model="sale_ends_at">
                            @error('sale_ends_at') <span class="error-text">{{ $message }}</span> @enderror
                        </label>

                        <label>
                            Panjang (cm)
                            <input type="number" wire:model="length_cm" min="1" placeholder="Opsional">
                            @error('length_cm') <span class="error-text">{{ $message }}</span> @enderror
                        </label>

                        <label>
                            Lebar (cm)
                            <input type="number" wire:model="width_cm" min="1" placeholder="Opsional">
                            @error('width_cm') <span class="error-text">{{ $message }}</span> @enderror
                        </label>

                        <label>
                            Tinggi (cm)
                            <input type="number" wire:model="height_cm" min="1" placeholder="Opsional">
                            @error('height_cm') <span class="error-text">{{ $message }}</span> @enderror
                        </label>

                        <label>
                            Urutan Tampil
                            <input type="number" wire:model="sort_order" min="0">
                            @error('sort_order') <span class="error-text">{{ $message }}</span> @enderror
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
                            @error('imageFile') <span class="error-text">{{ $message }}</span> @enderror
                        </label>

                        <div class="admin-product-image-preview-v3">
                            @if($imageFile)
                                <p>Preview gambar baru</p>
                                <img src="{{ $imageFile->temporaryUrl() }}" alt="Preview" class="admin-product-thumb-large">
                            @elseif($currentImage)
                                <p>Gambar saat ini</p>
                                <img src="{{ Storage::url($currentImage) }}" alt="Current Image" class="admin-product-thumb-large">
                            @else
                                <p>Belum ada gambar</p>
                                <span>Upload gambar produk jika diperlukan.</span>
                            @endif
                        </div>
                    </div>

                    <label class="admin-product-description-v3">
                        Deskripsi Produk
                        <textarea wire:model="description" rows="5" placeholder="Tulis deskripsi produk"></textarea>
                        @error('description') <span class="error-text">{{ $message }}</span> @enderror
                    </label>
                </div>
            </section>
        @endif

        <div class="admin-actions admin-product-actions-v3">
            <button class="admin-btn" type="submit">
                {{ $editingId ? 'Update Produk' : 'Simpan Produk' }}
            </button>

            <button class="admin-btn secondary" type="button" wire:click="resetForm">
                Reset
            </button>
        </div>
    </form>

    <div class="admin-product-table-toolbar">
        <div class="admin-product-export-import">
            <button type="button" class="admin-modern-btn admin-modern-btn-dark" wire:click="openExportModal">
                Export Excel
            </button>

            <button type="button" class="admin-modern-btn admin-modern-btn-light" wire:click="openImportModal">
                Import Excel
            </button>
        </div>
    </div>

    <div class="admin-panel-v2">
        <div class="admin-table-head">
            <div>
                <h2>Data Produk</h2>
                <p>Default menampilkan 10 data. Bisa dicari dan difilter berdasarkan kategori.</p>
            </div>

            <div class="admin-table-tools">
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Cari produk, SKU, brand, kategori..."
                >

                <select wire:model.live="categoryFilter">
                    <option value="">Semua kategori</option>

                    @foreach($this->categories as $category)
                        <option value="{{ $category->id }}">
                            {{ $category->parent_id ? '— ' : '' }}{{ $category->name }}
                        </option>
                    @endforeach
                </select>

                <select wire:model.live="perPage">
                    <option value="10">10 data</option>
                    <option value="20">20 data</option>
                    <option value="50">50 data</option>
                    <option value="100">100 data</option>
                </select>
            </div>
        </div>

        <table class="admin-table-v2">
            <thead>
                <tr>
                    <th>Gambar</th>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Brand</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Berat</th>
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
                                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="admin-table-thumb">
                            @else
                                -
                            @endif
                        </td>

                        <td>
                            <strong>{{ $product->name }}</strong>
                            <br>
                            <small>{{ $product->sku ?? 'Tanpa SKU' }}</small>
                        </td>

                        <td>
                            @if($product->category?->parent)
                                <small>{{ $product->category->parent->name }}</small>
                                <br>
                            @endif

                            {{ $product->category?->name ?? '-' }}
                        </td>

                        <td>{{ $product->brand?->name ?? '-' }}</td>

                        <td>
                            @if($product->is_promo_active)
                                <small style="text-decoration: line-through;">
                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                </small>
                                <br>
                                <strong>Rp {{ number_format($product->final_price, 0, ',', '.') }}</strong>
                                <br>
                                <small>Promo aktif</small>
                            @elseif($product->sale_price)
                                <strong>Rp {{ number_format($product->price, 0, ',', '.') }}</strong>
                                <br>
                                <small>Promo nonaktif / di luar tanggal</small>
                            @else
                                <strong>Rp {{ number_format($product->price, 0, ',', '.') }}</strong>
                            @endif

                            @if($product->sale_starts_at || $product->sale_ends_at)
                                <br>
                                <small>
                                    {{ $product->sale_starts_at?->format('d M Y H:i') ?? 'Sekarang' }}
                                    -
                                    {{ $product->sale_ends_at?->format('d M Y H:i') ?? 'Tanpa batas' }}
                                </small>
                            @endif
                        </td>

                        <td>{{ $product->stock }}</td>

                        <td>
                            <strong>{{ $product->formatted_weight }}</strong>
                            <br>
                            <small>{{ $product->dimensions_label }}</small>
                        </td>

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

                            <button
                                class="admin-btn danger"
                                type="button"
                                wire:click="delete({{ $product->id }})"
                                wire:confirm="Yakin ingin menghapus produk ini?"
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">Belum ada produk.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="admin-pagination">
            {{ $this->products->links('components.pagination.compify') }}
        </div>
    </div>

    @php
        $requiredProductFields = [
            'name' => 'Nama Produk',
            'slug' => 'Slug',
            'category_slug' => 'Slug Kategori',
            'category_name' => 'Nama Kategori',
            'price' => 'Harga Normal',
            'stock' => 'Stok',
            'is_active' => 'Status Aktif',
            'sort_order' => 'Urutan',
        ];

        $optionalProductFields = [
            'sku' => 'SKU',
            'brand_slug' => 'Slug Brand',
            'brand_name' => 'Nama Brand',
            'description' => 'Deskripsi',
            'sale_price' => 'Harga Diskon',
            'sale_starts_at' => 'Promo Mulai',
            'sale_ends_at' => 'Promo Selesai',
            'image' => 'Path Gambar',
            'is_featured' => 'Produk Unggulan',
            'is_new' => 'Produk Baru',
            'created_at' => 'Dibuat Pada',
            'updated_at' => 'Diupdate Pada',
        ];
    @endphp

    @if($showExportModal)
        <div class="admin-modal-backdrop">
            <div class="admin-modal-card">
                <div class="admin-modal-head">
                    <div>
                        <h2>Export Produk</h2>
                    </div>

                    <button type="button" wire:click="closeExportModal">×</button>
                </div>

                <form method="GET" action="{{ route('admin.catalog.products.export') }}" class="admin-modal-form">
                    <h3>Kolom Wajib</h3>

                    <div class="admin-field-check-grid">
                        @foreach($requiredProductFields as $field => $label)
                            <label class="admin-field-check disabled">
                                <input type="hidden" name="fields[]" value="{{ $field }}">
                                <input type="checkbox" checked disabled>
                                <span>{{ $label }}</span>
                                <small>Wajib</small>
                            </label>
                        @endforeach
                    </div>

                    <h3>Kolom Optional</h3>

                    <div class="admin-field-check-grid">
                        @foreach($optionalProductFields as $field => $label)
                            <label class="admin-field-check">
                                <input type="checkbox" name="fields[]" value="{{ $field }}" checked>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="admin-modal-actions">
                        <button type="button" class="admin-modern-btn admin-modern-btn-light" wire:click="closeExportModal">
                            Batal
                        </button>

                        <button type="submit" class="admin-modern-btn admin-modern-btn-dark">
                            Download Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($showImportModal)
        <div class="admin-modal-backdrop">
            <div class="admin-modal-card">
                <div class="admin-modal-head">
                    <div>
                        <h2>Import Produk</h2>
                    </div>

                    <button type="button" wire:click="closeImportModal">×</button>
                </div>

                <form method="POST" action="{{ route('admin.catalog.products.import') }}" enctype="multipart/form-data" class="admin-modal-form">
                    @csrf

                    <label class="admin-modal-file">
                        File Excel
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required>
                    </label>

                    <label class="admin-modal-file">
                        Mode Import
                        <select name="mode" required>
                            <option value="upsert">Tambah baru & update data lama</option>
                            <option value="create_only">Hanya tambah produk baru</option>
                            <option value="update_only">Hanya update produk lama</option>
                        </select>
                    </label>

                    <h3>Kolom Wajib</h3>

                    <div class="admin-field-check-grid">
                        @foreach($requiredProductFields as $field => $label)
                            <label class="admin-field-check disabled">
                                <input type="hidden" name="fields[]" value="{{ $field }}">
                                <input type="checkbox" checked disabled>
                                <span>{{ $label }}</span>
                                <small>Wajib</small>
                            </label>
                        @endforeach
                    </div>

                    <h3>Kolom Optional yang Diproses</h3>

                    <div class="admin-field-check-grid">
                        @foreach($optionalProductFields as $field => $label)
                            <label class="admin-field-check">
                                <input type="checkbox" name="fields[]" value="{{ $field }}" checked>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="admin-modal-actions">
                        <button type="button" class="admin-modern-btn admin-modern-btn-light" wire:click="closeImportModal">
                            Batal
                        </button>

                        <button type="submit" class="admin-modern-btn admin-modern-btn-dark">
                            Import Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>