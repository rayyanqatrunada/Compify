<?php

use App\Models\Category;
use App\Models\HomeCategoryGridItem;
use App\Models\HomeCategoryGridSetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new
#[Layout('components.layouts.admin')]
#[Title('Home Category Grid - Admin Compify')]
class extends Component
{
    use WithFileUploads;
    use WithPagination;

    public int $perPage = 10;

    public string $title = '';
    public string $subtitle = '';
    public int $columns_desktop = 6;
    public int $columns_tablet = 4;
    public int $columns_mobile = 2;
    public bool $section_is_active = true;

    public ?int $editingId = null;
    public ?int $category_id = null;
    public string $custom_name = '';
    public int $sort_order = 0;
    public bool $is_active = true;

    public $imageFile = null;
    public ?string $currentImage = null;

    public function mount(): void
    {
        $setting = HomeCategoryGridSetting::current();

        $this->title = $setting->title;
        $this->subtitle = $setting->subtitle ?? '';
        $this->columns_desktop = $setting->columns_desktop;
        $this->columns_tablet = $setting->columns_tablet;
        $this->columns_mobile = $setting->columns_mobile;
        $this->section_is_active = $setting->is_active;
    }

    #[Computed]
    public function categories()
    {
        return Category::active()
            ->orderByRaw('parent_id is not null')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function items()
    {
        return HomeCategoryGridItem::query()
            ->with('category.parent')
            ->orderBy('sort_order')
            ->latest()
            ->paginate($this->perPage);
    }

    public function saveSetting(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'columns_desktop' => ['required', 'integer', 'min:2', 'max:8'],
            'columns_tablet' => ['required', 'integer', 'min:2', 'max:6'],
            'columns_mobile' => ['required', 'integer', 'min:1', 'max:3'],
            'section_is_active' => ['boolean'],
        ]);

        HomeCategoryGridSetting::current()->update([
            'title' => $this->title,
            'subtitle' => $this->subtitle ?: null,
            'columns_desktop' => $this->columns_desktop,
            'columns_tablet' => $this->columns_tablet,
            'columns_mobile' => $this->columns_mobile,
            'is_active' => $this->section_is_active,
        ]);

        session()->flash('success', 'Setting category grid berhasil disimpan.');
    }

    public function saveItem(): void
    {
        $this->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'custom_name' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'imageFile' => ['nullable', 'image', 'max:4096'],
        ]);

        $exists = HomeCategoryGridItem::query()
            ->where('category_id', $this->category_id)
            ->when($this->editingId, fn ($query) => $query->where('id', '!=', $this->editingId))
            ->exists();

        if ($exists) {
            $this->addError('category_id', 'Kategori ini sudah masuk ke grid.');
            return;
        }

        $payload = [
            'category_id' => $this->category_id,
            'custom_name' => $this->custom_name ?: null,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];

        if ($this->imageFile) {
            if ($this->currentImage && Storage::disk('public')->exists($this->currentImage)) {
                Storage::disk('public')->delete($this->currentImage);
            }

            $payload['image'] = $this->imageFile->store('home/category-grid', 'public');
        }

        if ($this->editingId) {
            HomeCategoryGridItem::findOrFail($this->editingId)->update($payload);
        } else {
            HomeCategoryGridItem::create($payload);
        }

        session()->flash('success', 'Item category grid berhasil disimpan.');
        $this->resetItemForm();
    }

    public function editItem(int $id): void
    {
        $item = HomeCategoryGridItem::findOrFail($id);

        $this->editingId = $item->id;
        $this->category_id = $item->category_id;
        $this->custom_name = $item->custom_name ?? '';
        $this->sort_order = $item->sort_order;
        $this->is_active = $item->is_active;
        $this->currentImage = $item->image;
        $this->imageFile = null;

        $this->resetValidation();
    }

    public function deleteItem(int $id): void
    {
        $item = HomeCategoryGridItem::findOrFail($id);

        if ($item->image && Storage::disk('public')->exists($item->image)) {
            Storage::disk('public')->delete($item->image);
        }

        $item->delete();

        session()->flash('success', 'Item category grid berhasil dihapus.');
        $this->resetItemForm();
    }

    public function resetItemForm(): void
    {
        $this->editingId = null;
        $this->category_id = null;
        $this->custom_name = '';
        $this->sort_order = 0;
        $this->is_active = true;
        $this->imageFile = null;
        $this->currentImage = null;

        $this->resetValidation();
    }

    public function imageUrl(?string $path): ?string
    {
        return $path ? Storage::url($path) : null;
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <h2>Home Category Grid</h2>
        <p>Atur grid kategori 1:1 yang tampil tepat di bawah hero atau slider home.</p>
    </div>

    @if(session('success'))
        <div class="admin-alert-v2 admin-alert-v2--success">
            {{ session('success') }}
        </div>
    @endif

    <div class="admin-grid admin-grid-2">
        <form wire:submit="saveSetting" class="admin-panel-v2 admin-form">
            <h2>Setting Section</h2>

            <label>
                Judul Section
                <input type="text" wire:model="title" placeholder="Kategori Pilihan">
            </label>

            <label>
                Subtitle
                <input type="text" wire:model="subtitle" placeholder="Opsional">
            </label>

            <div class="admin-grid">
                <label>
                    Kolom Desktop
                    <input type="number" min="2" max="8" wire:model="columns_desktop">
                </label>

                <label>
                    Kolom Tablet
                    <input type="number" min="2" max="6" wire:model="columns_tablet">
                </label>

                <label>
                    Kolom Mobile
                    <input type="number" min="1" max="3" wire:model="columns_mobile">
                </label>

                <label>
                    Status Section
                    <select wire:model="section_is_active">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </label>
            </div>

            <button type="submit" class="admin-btn">
                Simpan Setting
            </button>
        </form>

        <form wire:submit="saveItem" class="admin-panel-v2 admin-form">
            <h2>{{ $editingId ? 'Edit Item Kategori' : 'Tambah Item Kategori' }}</h2>

            <label>
                Pilih Kategori
                <select wire:model="category_id">
                    <option value="">Pilih kategori</option>

                    @foreach($this->categories as $category)
                        <option value="{{ $category->id }}">
                            {{ $category->parent ? $category->parent->name . ' / ' : '' }}{{ $category->name }}
                        </option>
                    @endforeach
                </select>

                @error('category_id')
                    <small style="color:#b91c1c;">{{ $message }}</small>
                @enderror
            </label>

            <label>
                Nama Custom
                <input type="text" wire:model="custom_name" placeholder="Kosongkan untuk pakai nama kategori">
            </label>

            <div class="admin-grid">
                <label>
                    Urutan
                    <input type="number" min="0" wire:model="sort_order">
                </label>

                <label>
                    Status
                    <select wire:model="is_active">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </label>
            </div>

            <label>
                Gambar 1:1
                <input type="file" wire:model="imageFile" accept="image/*">
                <small class="admin-form-help-v2">
                    Rekomendasi gambar 600x600px atau 800x800px.
                </small>
            </label>

            <div class="home-category-grid-admin-preview">
                @if($imageFile)
                    <img src="{{ $imageFile->temporaryUrl() }}" alt="Preview">
                @elseif($currentImage)
                    <img src="{{ Storage::url($currentImage) }}" alt="Preview">
                @else
                    <span>Preview 1:1</span>
                @endif
            </div>

            <div class="admin-actions">
                <button type="submit" class="admin-btn">
                    {{ $editingId ? 'Update Item' : 'Tambah Item' }}
                </button>

                <button type="button" wire:click="resetItemForm" class="admin-btn secondary">
                    Reset
                </button>
            </div>
        </form>
    </div>

    <div class="admin-panel-v2">
        <div class="admin-table-head">
            <h2>Daftar Kategori Grid</h2>

            <select wire:model.live="perPage">
                <option value="10">10 data</option>
                <option value="20">20 data</option>
                <option value="50">50 data</option>
            </select>
        </div>

        <table class="admin-table-v2">
            <thead>
                <tr>
                    <th>Gambar</th>
                    <th>Kategori</th>
                    <th>Nama Tampil</th>
                    <th>Status</th>
                    <th>Urutan</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse($this->items as $item)
                    <tr>
                        <td>
                            @if($item->image)
                                <img src="{{ Storage::url($item->image) }}" class="admin-table-thumb" alt="{{ $item->display_name }}">
                            @else
                                -
                            @endif
                        </td>

                        <td>
                            <strong>{{ $item->category?->name ?? '-' }}</strong>
                            @if($item->category?->parent)
                                <small>{{ $item->category->parent->name }}</small>
                            @endif
                        </td>

                        <td>{{ $item->display_name }}</td>

                        <td>{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</td>

                        <td>{{ $item->sort_order }}</td>

                        <td>
                            <button type="button" class="admin-btn" wire:click="editItem({{ $item->id }})">
                                Edit
                            </button>

                            <button
                                type="button"
                                class="admin-btn danger"
                                wire:click="deleteItem({{ $item->id }})"
                                wire:confirm="Yakin hapus item kategori ini?"
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Belum ada kategori grid.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="admin-pagination">
            {{ $this->items->links() }}
        </div>
    </div>
</div>