<?php

use App\Models\Category;
use App\Models\ComboPackage;
use App\Models\ComboPackageItem;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('components.layouts.admin')]
#[Title('Paket Kombo Event - Compify')]
class extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public string $name = '';
    public string $slug = '';
    public ?string $subtitle = null;
    public ?string $description = null;

    public string $discount_type = 'percent';
    public string $discount_value = '0';

    public bool $is_active = true;
    public int $sort_order = 0;

    public $image = null;
    public ?string $currentImage = null;

    public string $selected_category_id = '';
    public string $product_search = '';

    public array $items = [];

    public function mount(): void
    {
        $this->resetItems();
    }

    public function updatedName(): void
    {
        if (! $this->editingId || $this->slug === '') {
            $this->slug = Str::slug($this->name);
        }
    }

    public function getPackagesProperty()
    {
        return ComboPackage::query()
            ->withCount('items')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function getCategoriesProperty()
    {
        return Category::query()
            ->with('parent')
            ->active()
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getFilteredProductsProperty()
    {
        $search = trim($this->product_search);

        return Product::query()
            ->with(['category', 'brand'])
            ->active()
            ->when($this->selected_category_id !== '', function ($query) {
                $category = Category::query()->find((int) $this->selected_category_id);

                if ($category && method_exists($category, 'selfAndActiveDescendantIds')) {
                    $query->whereIn('category_id', $category->selfAndActiveDescendantIds());
                } else {
                    $query->where('category_id', (int) $this->selected_category_id);
                }
            })
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';

                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like)
                        ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', $like))
                        ->orWhereHas('brand', fn ($brandQuery) => $brandQuery->where('name', 'like', $like));
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'category_id', 'brand_id', 'name', 'sku', 'price', 'sale_price', 'stock']);
    }

    public function getSelectedProductsProperty()
    {
        $ids = collect($this->items)
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    public function getOriginalTotalProperty(): int
    {
        return collect($this->items)->sum(function ($item) {
            $productId = $item['product_id'] ?? null;

            if (! $productId) {
                return 0;
            }

            $product = $this->selectedProducts->get((int) $productId);
            $quantity = max(1, (int) ($item['quantity'] ?? 1));

            return (int) (($product?->final_price ?? 0) * $quantity);
        });
    }

    public function getDiscountAmountProperty(): int
    {
        $originalTotal = (int) $this->originalTotal;

        if ($originalTotal <= 0) {
            return 0;
        }

        $discountValue = max(0, (float) str_replace(',', '.', $this->discount_value));

        if ($this->discount_type === 'amount') {
            return min($originalTotal, (int) round($discountValue));
        }

        $percent = min(100, $discountValue);

        return (int) round($originalTotal * ($percent / 100));
    }

    public function getPackagePriceProperty(): int
    {
        return max(0, $this->originalTotal - $this->discountAmount);
    }

    public function addItem(): void
    {
        $this->items[] = [
            'product_id' => '',
            'quantity' => 1,
            'sort_order' => count($this->items),
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);

        $this->items = array_values($this->items);
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => [
                'required',
                'string',
                'max:180',
                Rule::unique('combo_packages', 'slug')->ignore($this->editingId),
            ],
            'subtitle' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:1000'],
            'discount_type' => ['required', 'in:percent,amount'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:3072'],
            'items' => ['required', 'array', 'min:2'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        if ($data['discount_type'] === 'percent' && $data['discount_value'] > 100) {
            $this->addError('discount_value', 'Diskon persen maksimal 100.');
            return;
        }

        $productIds = collect($data['items'])->pluck('product_id')->filter()->values();

        if ($productIds->duplicates()->isNotEmpty()) {
            $this->addError('items', 'Produk dalam satu paket tidak boleh duplikat.');
            return;
        }

        $itemData = $data['items'];

        unset($data['items'], $data['image']);

        $data['package_price'] = $this->packagePrice;

        if ($this->image) {
            if ($this->currentImage) {
                Storage::disk('public')->delete($this->currentImage);
            }

            $data['image'] = $this->image->store('event/combo-packages', 'public');
        }

        $package = ComboPackage::query()->updateOrCreate(
            ['id' => $this->editingId],
            $data
        );

        $package->items()->delete();

        foreach ($itemData as $item) {
            $package->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'sort_order' => $item['sort_order'],
            ]);
        }

        $this->resetForm();

        session()->flash('success', 'Paket bundling berhasil disimpan.');
    }

    public function edit(int $id): void
    {
        $package = ComboPackage::with('items')->findOrFail($id);

        $this->editingId = $package->id;
        $this->name = $package->name;
        $this->slug = $package->slug;
        $this->subtitle = $package->subtitle;
        $this->description = $package->description;
        $this->discount_type = $package->discount_type ?? 'percent';
        $this->discount_value = (string) ($package->discount_value ?? 0);$this->discount_value = $package->discount_value ?? 0;
        $this->is_active = (bool) $package->is_active;
        $this->sort_order = (int) $package->sort_order;
        $this->currentImage = $package->image;
        $this->image = null;

        $this->items = $package->items
            ->map(fn (ComboPackageItem $item) => [
                'product_id' => (string) $item->product_id,
                'quantity' => $item->quantity,
                'sort_order' => $item->sort_order,
            ])
            ->values()
            ->all();
    }

    public function delete(int $id): void
    {
        $package = ComboPackage::findOrFail($id);

        if ($package->image) {
            Storage::disk('public')->delete($package->image);
        }

        $package->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        session()->flash('success', 'Paket bundling berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->slug = '';
        $this->subtitle = null;
        $this->description = null;
        $this->discount_type = 'percent';
        $this->is_active = true;
        $this->sort_order = 0;
        $this->image = null;
        $this->currentImage = null;
        $this->discount_value = '0';
        $this->selected_category_id = '';
        $this->product_search = '';

        $this->resetItems();
        $this->resetValidation();
    }

    private function resetItems(): void
    {
        $this->items = [
            ['product_id' => '', 'quantity' => 1, 'sort_order' => 0],
            ['product_id' => '', 'quantity' => 1, 'sort_order' => 1],
        ];
    }

    public function imageUrl(?string $path): ?string
    {
        return $path ? Storage::url($path) : null;
    }

    public function formatRupiah(int|float|null $value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }
};
?>

<div class="admin-page-v2 admin-event-page-v2">
    <div class="admin-section-title-v2">
        <div>
            <h2>Paket Bundling</h2>
            <p>Gabungkan beberapa produk menjadi satu paket dengan diskon otomatis dari total harga barang.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="admin-alert-v2 admin-alert-v2--success">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="admin-panel-v2 admin-form-v2">
        <div class="admin-grid-v2 admin-grid-v2--event-settings">
            <label>
                <span>Nama Paket</span>
                <input type="text" wire:model.live="name" placeholder="Contoh: Paket Gaming Hemat">
                @error('name') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Slug</span>
                <input type="text" wire:model="slug" placeholder="paket-gaming-hemat">
                @error('slug') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Subtitle</span>
                <input type="text" wire:model="subtitle" placeholder="Deskripsi singkat paket">
                @error('subtitle') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Urutan</span>
                <input type="number" min="0" wire:model="sort_order">
                @error('sort_order') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Gambar Paket</span>
                <input type="file" wire:model="image" accept="image/*">
                @error('image') <small>{{ $message }}</small> @enderror
            </label>
        </div>

        @if ($image)
            <div class="admin-preview-v2">
                <span>Preview gambar baru</span>
                <img src="{{ $image->temporaryUrl() }}" alt="Preview paket">
            </div>
        @elseif ($currentImage)
            <div class="admin-preview-v2">
                <span>Gambar saat ini</span>
                <img src="{{ $this->imageUrl($currentImage) }}" alt="Paket bundling">
            </div>
        @endif

        <label>
            <span>Deskripsi</span>
            <textarea rows="4" wire:model="description" placeholder="Detail paket bundling"></textarea>
            @error('description') <small>{{ $message }}</small> @enderror
        </label>

        <div class="admin-nested-v2">
            <div class="admin-nested-v2__head">
                <div>
                    <strong>Produk Dalam Paket</strong>
                    <p class="admin-muted-v2">Pilih kategori atau gunakan search untuk mempersempit pilihan produk.</p>
                </div>

                <button type="button" wire:click="addItem" class="admin-btn-v2 admin-btn-v2--sm">
                    Tambah Produk
                </button>
            </div>

            @error('items') <small>{{ $message }}</small> @enderror

            <div class="admin-grid-v2 admin-grid-v2--event-settings">
                <label>
                    <span>Filter Kategori</span>
                    <select wire:model.live="selected_category_id">
                        <option value="">Semua kategori</option>

                        @foreach ($this->categories as $category)
                            <option value="{{ $category->id }}">
                                {{ $category->parent ? $category->parent->name . ' / ' : '' }}{{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span>Cari Produk</span>
                    <input type="search" wire:model.live.debounce.400ms="product_search" placeholder="Cari nama produk, SKU, brand, atau kategori">
                </label>
            </div>

            @foreach ($items as $index => $item)
                <div class="admin-nested-row-v2" wire:key="combo-item-{{ $index }}">
                    <label>
                        <span>Produk</span>
                        <select wire:model.live="items.{{ $index }}.product_id">
                            <option value="">Pilih produk</option>

                            @foreach ($this->filteredProducts as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->name }}
                                    — {{ $this->formatRupiah($product->final_price) }}
                                    — Stok: {{ $product->stock }}
                                </option>
                            @endforeach
                        </select>

                        @error('items.' . $index . '.product_id') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Qty</span>
                        <input type="number" min="1" wire:model.live="items.{{ $index }}.quantity">
                        @error('items.' . $index . '.quantity') <small>{{ $message }}</small> @enderror
                    </label>

                    <label>
                        <span>Urutan</span>
                        <input type="number" min="0" wire:model="items.{{ $index }}.sort_order">
                        @error('items.' . $index . '.sort_order') <small>{{ $message }}</small> @enderror
                    </label>

                    <button type="button" wire:click="removeItem({{ $index }})" class="admin-btn-v2 admin-btn-v2--danger">
                        Hapus
                    </button>
                </div>
            @endforeach
        </div>

        <div class="admin-discount-box-v2">
            <div class="admin-grid-v2 admin-grid-v2--event-settings">
                <label>
                    <span>Tipe Diskon Paket</span>
                    <select wire:model.live="discount_type">
                        <option value="percent">Persen dari total barang</option>
                        <option value="amount">Nominal Rupiah</option>
                    </select>
                    @error('discount_type') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    <span>Nilai Diskon</span>
                    <input
                        type="number"
                        min="0"
                        step="0.01"
                        wire:model.live="discount_value"
                        placeholder="{{ $discount_type === 'percent' ? 'Contoh: 10' : 'Contoh: 50000' }}"
                    >
                    @error('discount_value') <small>{{ $message }}</small> @enderror
                </label>
            </div>

            <div class="admin-price-summary-v2">
                <div>
                    <span>Total Harga Barang</span>
                    <strong>{{ $this->formatRupiah($this->originalTotal) }}</strong>
                </div>

                <div>
                    <span>Diskon Paket</span>
                    <strong>- {{ $this->formatRupiah($this->discountAmount) }}</strong>
                </div>

                <div class="is-final">
                    <span>Harga Akhir Paket</span>
                    <strong>{{ $this->formatRupiah($this->packagePrice) }}</strong>
                </div>
            </div>
        </div>

        <label class="admin-check-v2">
            <input type="checkbox" wire:model="is_active">
            <span>Aktifkan paket bundling</span>
        </label>

        <div class="admin-actions-v2">
            <button type="submit" class="admin-btn-v2 admin-btn-v2--primary">
                {{ $editingId ? 'Update Paket' : 'Tambah Paket' }}
            </button>

            @if ($editingId)
                <button type="button" wire:click="resetForm" class="admin-btn-v2">
                    Batal Edit
                </button>
            @endif
        </div>
    </form>

    <div class="admin-panel-v2 admin-table-wrap-v2">
        <table class="admin-table-v2">
            <thead>
                <tr>
                    <th>Paket</th>
                    <th>Slug</th>
                    <th>Diskon</th>
                    <th>Harga Paket</th>
                    <th>Jumlah Produk</th>
                    <th>Status</th>
                    <th>Urutan</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($this->packages as $package)
                    <tr>
                        <td><strong>{{ $package->name }}</strong></td>
                        <td>{{ $package->slug }}</td>
                        <td>{{ $package->discount_label }}</td>
                        <td>{{ $package->formatted_package_price }}</td>
                        <td>{{ $package->items_count }}</td>
                        <td>
                            <span class="{{ $package->is_active ? 'admin-status-v2 admin-status-v2--active' : 'admin-status-v2 admin-status-v2--inactive' }}">
                                {{ $package->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>{{ $package->sort_order }}</td>
                        <td>
                            <div class="admin-table-actions-v2">
                                <a
                                    href="{{ route('event.packages.show', $package) }}"
                                    target="_blank"
                                    class="admin-btn-v2 admin-btn-v2--sm"
                                >
                                    Lihat
                                </a>

                                <button type="button" wire:click="edit({{ $package->id }})" class="admin-btn-v2 admin-btn-v2--sm">
                                    Edit
                                </button>

                                <button
                                    type="button"
                                    wire:click="delete({{ $package->id }})"
                                    wire:confirm="Hapus paket bundling ini?"
                                    class="admin-btn-v2 admin-btn-v2--sm admin-btn-v2--danger"
                                >
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">Belum ada paket bundling.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>