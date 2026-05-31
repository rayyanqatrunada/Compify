<?php

use App\Models\Category;
use App\Models\EventFlashSaleGroup;
use App\Models\EventFlashSaleItem;
use App\Models\Product;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.admin')]
#[Title('Flash Sale Event - Compify')]
class extends Component
{
    public ?int $selectedGroupId = null;

    public ?int $editingGroupId = null;
    public string $group_name = '';
    public bool $group_is_active = true;
    public int $group_sort_order = 0;

    public ?int $editingId = null;
    public ?int $product_id = null;
    public ?int $selected_category_id = null;
    public string $product_search = '';
    public string $discount_type = 'percent';
    public float|int|string $discount_value = 0;
    public ?int $stock_limit = null;
    public bool $is_active = true;
    public int $sort_order = 0;

    public function mount(): void
    {
        $group = EventFlashSaleGroup::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if (! $group) {
            $group = EventFlashSaleGroup::query()->create([
                'name' => 'Flash Sale Utama',
                'is_active' => true,
                'sort_order' => 0,
            ]);
        }

        $this->selectedGroupId = $group->id;
    }

    public function getGroupsProperty()
    {
        return EventFlashSaleGroup::query()
            ->withCount('items')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function getSelectedGroupProperty(): ?EventFlashSaleGroup
    {
        return $this->selectedGroupId
            ? EventFlashSaleGroup::query()->find($this->selectedGroupId)
            : null;
    }

    public function getItemsProperty()
    {
        return EventFlashSaleItem::query()
            ->with(['product.category', 'group'])
            ->where('event_flash_sale_group_id', $this->selectedGroupId)
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
            ->when($this->selected_category_id, function ($query) {
                $category = Category::query()->find($this->selected_category_id);

                if ($category && method_exists($category, 'selfAndActiveDescendantIds')) {
                    $query->whereIn('category_id', $category->selfAndActiveDescendantIds());
                } else {
                    $query->where('category_id', $this->selected_category_id);
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
            ->limit(40)
            ->get(['id', 'category_id', 'brand_id', 'name', 'sku', 'price', 'sale_price', 'stock']);
    }

    public function selectGroup(int $id): void
    {
        $this->selectedGroupId = $id;
        $this->resetItemForm();
    }

    public function saveGroup(): void
    {
        $data = $this->validate([
            'group_name' => ['required', 'string', 'max:120'],
            'group_is_active' => ['boolean'],
            'group_sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $group = EventFlashSaleGroup::query()->updateOrCreate(
            ['id' => $this->editingGroupId],
            [
                'name' => $data['group_name'],
                'is_active' => $data['group_is_active'],
                'sort_order' => $data['group_sort_order'],
            ]
        );

        $this->selectedGroupId = $group->id;
        $this->resetGroupForm();

        session()->flash('success', 'Group flash sale berhasil disimpan.');
    }

    public function editGroup(int $id): void
    {
        $group = EventFlashSaleGroup::query()->findOrFail($id);

        $this->editingGroupId = $group->id;
        $this->group_name = $group->name;
        $this->group_is_active = (bool) $group->is_active;
        $this->group_sort_order = (int) $group->sort_order;
        $this->selectedGroupId = $group->id;
    }

    public function toggleGroup(int $id): void
    {
        $group = EventFlashSaleGroup::query()->findOrFail($id);

        $group->update([
            'is_active' => ! $group->is_active,
        ]);

        session()->flash('success', 'Status group flash sale berhasil diubah.');
    }

    public function deleteGroup(int $id): void
    {
        $group = EventFlashSaleGroup::query()->findOrFail($id);
        $group->delete();

        $this->selectedGroupId = EventFlashSaleGroup::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');

        $this->resetGroupForm();
        $this->resetItemForm();

        session()->flash('success', 'Group flash sale berhasil dihapus.');
    }

    public function saveItem(): void
    {
        if (! $this->selectedGroupId) {
            $this->addError('selectedGroupId', 'Pilih group flash sale terlebih dahulu.');
            return;
        }

        $data = $this->validate([
            'selectedGroupId' => ['required', 'integer', Rule::exists('event_flash_sale_groups', 'id')],
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id'),
                Rule::unique('event_flash_sale_items', 'product_id')->ignore($this->editingId),
            ],
            'discount_type' => ['required', 'in:percent,amount'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'stock_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        if ($data['discount_type'] === 'percent' && $data['discount_value'] > 100) {
            $this->addError('discount_value', 'Diskon persen maksimal 100.');
            return;
        }

        EventFlashSaleItem::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'event_flash_sale_group_id' => $this->selectedGroupId,
                'product_id' => $data['product_id'],
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'],
                'stock_limit' => $data['stock_limit'],
                'is_active' => $data['is_active'],
                'sort_order' => $data['sort_order'],
            ]
        );

        $this->resetItemForm();

        session()->flash('success', 'Produk flash sale berhasil disimpan.');
    }

    public function editItem(int $id): void
    {
        $item = EventFlashSaleItem::query()->findOrFail($id);

        $this->selectedGroupId = $item->event_flash_sale_group_id;
        $this->editingId = $item->id;
        $this->product_id = $item->product_id;
        $this->discount_type = $item->discount_type;
        $this->discount_value = $item->discount_value;
        $this->stock_limit = $item->stock_limit;
        $this->is_active = (bool) $item->is_active;
        $this->sort_order = (int) $item->sort_order;
    }

    public function deleteItem(int $id): void
    {
        EventFlashSaleItem::query()->findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->resetItemForm();
        }

        session()->flash('success', 'Produk flash sale berhasil dihapus.');
    }

    public function resetGroupForm(): void
    {
        $this->editingGroupId = null;
        $this->group_name = '';
        $this->group_is_active = true;
        $this->group_sort_order = 0;

        $this->resetValidation();
    }

    public function resetItemForm(): void
    {
        $this->editingId = null;
        $this->product_id = null;
        $this->selected_category_id = null;
        $this->product_search = '';
        $this->discount_type = 'percent';
        $this->discount_value = 0;
        $this->stock_limit = null;
        $this->is_active = true;
        $this->sort_order = 0;

        $this->resetValidation();
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
            <h2>Flash Sale</h2>
            <p>Kelola group flash sale, lalu pilih produk berdasarkan kategori atau search.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="admin-alert-v2 admin-alert-v2--success">
            {{ session('success') }}
        </div>
    @endif

    <div class="admin-panel-v2 admin-form-v2">
        <h3>Group Flash Sale</h3>

        <form wire:submit="saveGroup" class="admin-grid-v2 admin-grid-v2--group">
            <label>
                <span>Nama Group</span>
                <input type="text" wire:model="group_name" placeholder="Contoh: Flash Sale Weekend">
                @error('group_name') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Urutan</span>
                <input type="number" min="0" wire:model="group_sort_order">
                @error('group_sort_order') <small>{{ $message }}</small> @enderror
            </label>

            <label class="admin-check-v2">
                <input type="checkbox" wire:model="group_is_active">
                <span>Group Aktif</span>
            </label>

            <div class="admin-actions-v2">
                <button type="submit" class="admin-btn-v2 admin-btn-v2--primary">
                    {{ $editingGroupId ? 'Update Group' : 'Tambah Group' }}
                </button>

                @if ($editingGroupId)
                    <button type="button" wire:click="resetGroupForm" class="admin-btn-v2">
                        Batal Edit
                    </button>
                @endif
            </div>
        </form>

        <div class="admin-flash-group-list-v2">
            @foreach ($this->groups as $group)
                <div class="admin-flash-group-card-v2 {{ $selectedGroupId === $group->id ? 'is-selected' : '' }}">
                    <button type="button" wire:click="selectGroup({{ $group->id }})">
                        <strong>{{ $group->name }}</strong>
                        <span>{{ $group->items_count }} produk</span>
                    </button>

                    <div>
                        <span class="{{ $group->is_active ? 'admin-status-v2 admin-status-v2--active' : 'admin-status-v2 admin-status-v2--inactive' }}">
                            {{ $group->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>

                        <button type="button" wire:click="toggleGroup({{ $group->id }})" class="admin-btn-v2 admin-btn-v2--sm">
                            {{ $group->is_active ? 'Matikan' : 'Hidupkan' }}
                        </button>

                        <button type="button" wire:click="editGroup({{ $group->id }})" class="admin-btn-v2 admin-btn-v2--sm">
                            Edit
                        </button>

                        <button
                            type="button"
                            wire:click="deleteGroup({{ $group->id }})"
                            wire:confirm="Hapus group ini? Semua item flash sale di dalamnya juga ikut terhapus."
                            class="admin-btn-v2 admin-btn-v2--sm admin-btn-v2--danger"
                        >
                            Hapus
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <form wire:submit="saveItem" class="admin-panel-v2 admin-form-v2">
        <div>
            <h3>Produk Flash Sale</h3>
            <p class="admin-muted-v2">
                Group aktif sekarang:
                <strong>{{ $this->selectedGroup?->name ?? 'Belum ada group' }}</strong>
            </p>
        </div>

        @error('selectedGroupId')
            <small>{{ $message }}</small>
        @enderror

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

            <label>
                <span>Produk</span>
                <select wire:model="product_id">
                    <option value="">Pilih produk</option>

                    @foreach ($this->filteredProducts as $product)
                        <option value="{{ $product->id }}">
                            {{ $product->name }}
                            — {{ $this->formatRupiah($product->final_price) }}
                            — Stok: {{ $product->stock }}
                        </option>
                    @endforeach
                </select>

                @error('product_id')
                    <small>{{ $message }}</small>
                @enderror
            </label>

            <label>
                <span>Tipe Diskon</span>
                <select wire:model.live="discount_type">
                    <option value="percent">Persen (%)</option>
                    <option value="amount">Nominal Rupiah</option>
                </select>

                @error('discount_type')
                    <small>{{ $message }}</small>
                @enderror
            </label>

            <label>
                <span>Nilai Diskon</span>
                <input
                    type="number"
                    min="0"
                    step="0.01"
                    wire:model="discount_value"
                    placeholder="{{ $discount_type === 'percent' ? 'Contoh: 10' : 'Contoh: 50000' }}"
                >

                @error('discount_value')
                    <small>{{ $message }}</small>
                @enderror
            </label>

            <label>
                <span>Stok Event</span>
                <input type="number" min="1" wire:model="stock_limit" placeholder="Kosongkan jika tidak dibatasi">
                @error('stock_limit') <small>{{ $message }}</small> @enderror
            </label>

            <label>
                <span>Urutan Tampil</span>
                <input type="number" min="0" wire:model="sort_order">
                @error('sort_order') <small>{{ $message }}</small> @enderror
            </label>
        </div>

        <label class="admin-check-v2">
            <input type="checkbox" wire:model="is_active">
            <span>Produk aktif di dalam group</span>
        </label>

        <div class="admin-help-v2">
            Produk hanya tampil di halaman event jika produk aktif, item aktif, dan group flash sale juga aktif.
        </div>

        <div class="admin-actions-v2">
            <button type="submit" class="admin-btn-v2 admin-btn-v2--primary">
                {{ $editingId ? 'Update Produk Flash Sale' : 'Tambah Produk Flash Sale' }}
            </button>

            @if ($editingId)
                <button type="button" wire:click="resetItemForm" class="admin-btn-v2">
                    Batal Edit
                </button>
            @endif
        </div>
    </form>

    <div class="admin-panel-v2 admin-table-wrap-v2">
        <table class="admin-table-v2">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Harga Awal</th>
                    <th>Diskon</th>
                    <th>Harga Event</th>
                    <th>Stok Event</th>
                    <th>Status</th>
                    <th>Urutan</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($this->items as $item)
                    <tr>
                        <td><strong>{{ $item->product?->name ?? 'Produk hilang' }}</strong></td>
                        <td>{{ $item->product?->category?->name ?? '-' }}</td>
                        <td>{{ $this->formatRupiah($item->base_price) }}</td>

                        <td>
                            @if ($item->discount_type === 'percent')
                                {{ number_format((float) $item->discount_value, 0) }}%
                            @else
                                {{ $this->formatRupiah($item->discount_value) }}
                            @endif
                        </td>

                        <td><strong>{{ $item->formatted_event_price }}</strong></td>
                        <td>{{ $item->stock_limit ?: '-' }}</td>

                        <td>
                            <span class="{{ $item->is_active ? 'admin-status-v2 admin-status-v2--active' : 'admin-status-v2 admin-status-v2--inactive' }}">
                                {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>

                        <td>{{ $item->sort_order }}</td>

                        <td>
                            <div class="admin-table-actions-v2">
                                <button type="button" wire:click="editItem({{ $item->id }})" class="admin-btn-v2 admin-btn-v2--sm">
                                    Edit
                                </button>

                                <button
                                    type="button"
                                    wire:click="deleteItem({{ $item->id }})"
                                    wire:confirm="Hapus item flash sale ini?"
                                    class="admin-btn-v2 admin-btn-v2--sm admin-btn-v2--danger"
                                >
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">Belum ada produk flash sale dalam group ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>