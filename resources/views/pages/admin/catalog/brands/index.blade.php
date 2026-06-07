<?php

use App\Models\Brand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('components.layouts.admin')]
#[Title('Admin Brands - Compify')]
class extends Component {

    use WithFileUploads;

    // ── State ────────────────────────────────────────────────────────────────

    public ?int   $editingId = null;
    public string $search    = '';

    // ── Form fields ──────────────────────────────────────────────────────────

    public string $name        = '';
    public string $slug        = '';
    public string $website_url = '';
    public bool   $is_active   = true;
    public int    $sort_order  = 0;
    public        $logoFile    = null;
    public ?string $currentLogo = null;

    // ── Navbar limit setting ─────────────────────────────────────────────────

    public int $navbarLimit = 16;

    // ── Lifecycle ────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->navbarLimit = (int) Cache::get('brand_navbar_limit', 16);
    }

    public function updatedName(string $value): void
    {
        if (empty($this->slug) || $this->editingId === null) {
            $this->slug = Str::slug($value);
        }
    }

    // ── Computed ─────────────────────────────────────────────────────────────

    #[Computed]
    public function brands()
    {
        return Brand::query()
            ->when($this->search, fn ($q) =>
                $q->where('name', 'like', '%' . $this->search . '%')
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    // ── Navbar limit ──────────────────────────────────────────────────────────

    public function saveNavbarLimit(): void
    {
        $this->validate(['navbarLimit' => ['required', 'integer', 'min:1', 'max:100']]);
        Cache::forever('brand_navbar_limit', $this->navbarLimit);
        session()->flash('success', 'Pengaturan limit navbar berhasil disimpan.');
    }

    // ── CRUD ─────────────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->validate([
            'name'        => ['required', 'string', 'max:100'],
            'slug'        => ['nullable', 'string', 'max:120'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'is_active'   => ['boolean'],
            'sort_order'  => ['required', 'integer', 'min:0'],
            'logoFile'    => ['nullable', 'image', 'max:2048'],
        ]);

        $slug = $this->slug ?: Str::slug($this->name);

        if (Brand::where('slug', $slug)->where('id', '!=', $this->editingId)->exists()) {
            $slug .= '-' . Str::lower(Str::random(5));
        }

        $payload = [
            'name'        => $this->name,
            'slug'        => $slug,
            'is_active'   => $this->is_active,
            'sort_order'  => $this->sort_order,
            'website_url' => $this->website_url ?: null,
        ];

        if ($this->logoFile) {
            if ($this->currentLogo && Storage::disk('public')->exists($this->currentLogo)) {
                Storage::disk('public')->delete($this->currentLogo);
            }
            $payload['logo'] = $this->logoFile->store('brands/logos', 'public');
        }

        if ($this->editingId) {
            Brand::findOrFail($this->editingId)->update($payload);
            session()->flash('success', 'Brand berhasil diperbarui.');
        } else {
            Brand::create($payload);
            session()->flash('success', 'Brand berhasil ditambahkan.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $brand = Brand::findOrFail($id);

        $this->editingId   = $brand->id;
        $this->name        = $brand->name;
        $this->slug        = $brand->slug;
        $this->is_active   = (bool) $brand->is_active;
        $this->sort_order  = $brand->sort_order;
        $this->website_url = $brand->website_url ?? '';
        $this->currentLogo = $brand->logo;
        $this->logoFile    = null;
    }

    public function delete(int $id): void
    {
        $brand = Brand::findOrFail($id);

        if ($brand->products()->exists()) {
            session()->flash('error', 'Brand ini masih punya produk. Pindahkan produk dulu sebelum menghapus.');
            return;
        }

        if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
            Storage::disk('public')->delete($brand->logo);
        }

        $brand->delete();
        session()->flash('success', 'Brand berhasil dihapus.');
        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $brand = Brand::findOrFail($id);
        $brand->update(['is_active' => ! $brand->is_active]);
        session()->flash('success', "Brand \"{$brand->name}\" berhasil " . ($brand->fresh()->is_active ? 'diaktifkan' : 'dinonaktifkan') . '.');
    }

    public function updateSortOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $item) {
            Brand::where('id', $item['id'])->update(['sort_order' => $item['order']]);
        }
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingId', 'name', 'slug', 'website_url',
            'logoFile', 'currentLogo',
        ]);
        $this->is_active  = true;
        $this->sort_order = 0;
    }
};
?>

<div>
    <!-- <h1>Kelola Brand</h1> -->

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash-error" style="background:#fee2e2;color:#b91c1c;padding:12px 16px;border-radius:8px;margin-bottom:16px;">{{ session('error') }}</div>
    @endif

    {{-- ── PENGATURAN NAVBAR ──────────────────────────────────── --}}
    <div class="admin-card admin-form" style="margin-bottom:24px;">
        <h2>Pengaturan Tampilan Navbar</h2>
        <div style="display:flex;align-items:flex-end;gap:16px;flex-wrap:wrap;">
            <label style="display:flex;flex-direction:column;gap:6px;font-size:14px;font-weight:600;">
                Jumlah brand di navbar (desktop &amp; mobile)
                <input
                    type="number"
                    wire:model="navbarLimit"
                    min="1"
                    max="100"
                    style="width:100px;"
                >
                <small style="font-weight:400;opacity:.6;">Brand diurutkan berdasarkan Sort Order lalu Nama.</small>
            </label>
            <button class="admin-btn" style="margin-left: auto;" type="button" wire:click="saveNavbarLimit">
                Simpan Pengaturan
            </button>
        </div>
    </div>

    {{-- ── FORM TAMBAH / EDIT ─────────────────────────────────── --}}
    <form wire:submit="save" class="admin-card admin-form">
        <h2>{{ $editingId ? 'Edit Brand' : 'Tambah Brand' }}</h2>

        <div class="admin-grid">

            <label>
                Nama Brand 
                <!-- <span style="color:#e53e3e">*</span> -->
                <input type="text" wire:model.live="name" placeholder="Contoh: ASUS, MSI, Corsair">
                @error('name') <span class="error-text">{{ $message }}</span> @enderror
            </label>

            <label>
                Slug
                <input type="text" wire:model="slug" placeholder="auto-generate dari nama">
                @error('slug') <span class="error-text">{{ $message }}</span> @enderror
            </label>

            <label>
                Urutan Tampil
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
                Logo Brand
                <input type="file" wire:model="logoFile" accept="image/png,image/jpeg,image/svg+xml,image/webp">
                @error('logoFile') <span class="error-text">{{ $message }}</span> @enderror
            </label>

            <div>
                @if($logoFile)
                    <p>Preview logo baru:</p>
                    <img src="{{ $logoFile->temporaryUrl() }}" alt="Preview" style="width:80px;height:80px;object-fit:contain;border-radius:8px;background:#f3f4f6;padding:8px;">
                @elseif($currentLogo)
                    <p>Logo saat ini:</p>
                    <img src="{{ Storage::url($currentLogo) }}" alt="Logo" style="width:80px;height:80px;object-fit:contain;border-radius:8px;background:#f3f4f6;padding:8px;">
                @endif
            </div>

        </div>

        <div class="admin-actions" style="margin-top:20px;">
            <button class="admin-btn" type="submit">
                {{ $editingId ? 'Update Brand' : 'Simpan Brand' }}
            </button>
            <button class="admin-btn secondary" type="button" wire:click="resetForm">
                Reset
            </button>
        </div>
    </form>

    {{-- ── TABEL DATA ──────────────────────────────────────────── --}}
    <div class="admin-card">
        <h2>Data Brand</h2>

        <div style="margin-bottom:16px;">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama brand..."
                style="max-width:300px;width:100%;"
            >
        </div>

        <table class="admin-table" id="brandsTable">
            <thead>
                <tr>
                    <th style="width:36px;">#</th>
                    <th style="width:64px;">Logo</th>
                    <th style="width:140px;">Nama</th>
                    <th style="width:120px;">Slug</th>
                    <!-- <th>Website</th> -->
                    <th style="width:70px;">Produk</th>
                    <th style="width:80px;">Urutan</th>
                    <th style="width:90px;">Status</th>
                    <th style="width:80px;">Aksi</th>
                    <th style="width:140px;">Ubah</th>
                </tr>
            </thead>
            <tbody id="brandsSortable">
                @forelse($this->brands as $brand)
                    <tr wire:key="brand-{{ $brand->id }}" data-id="{{ $brand->id }}">

                        <td class="sort-handle" title="Drag untuk atur urutan" style="cursor:grab;text-align:center;opacity:.4;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M8 6a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm8 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4ZM8 14a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm8 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4ZM8 22a2 2 0 1 1 0-4 2 2 0 0 1 0 4Zm8 0a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z"/>
                            </svg>
                        </td>

                        <td>
                            @if($brand->logo_url)
                                <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" style="width:48px;height:48px;object-fit:contain;border-radius:8px;background:#f3f4f6;padding:4px;">
                            @else
                                <span style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:8px;background:#e5e7eb;font-weight:700;font-size:13px;">{{ $brand->initials }}</span>
                            @endif
                        </td>

                        <td><strong>{{ $brand->name }}</strong></td>
                        <td><code style="font-size:12px;background:#f3f4f6;padding:2px 6px;border-radius:4px;">{{ $brand->slug }}</code></td>

                        <td>
                            @if($brand->website_url)
                                <a href="{{ $brand->website_url }}" target="_blank" rel="noopener noreferrer" style="font-size:12px;color:#3b82f6;text-decoration:underline;" title="{{ $brand->website_url }}">
                                    {{ parse_url($brand->website_url, PHP_URL_HOST) }}
                                </a>
                            @else
                                <span style="opacity:.3;">—</span>
                            @endif
                        </td>

                        <td>{{ $brand->products()->count() }}</td>
                        <td>{{ $brand->sort_order }}</td>

                        <td>
                            <button
                                wire:click="toggleActive({{ $brand->id }})"
                                class="admin-btn {{ $brand->is_active ? '' : 'secondary' }}"
                                style="font-size:12px;padding:4px 10px;"
                                title="{{ $brand->is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan' }}"
                            >
                                {{ $brand->is_active ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </td>

                        <td>
                            <button class="admin-btn" type="button" wire:click="edit({{ $brand->id }})">
                                Edit
                            </button>
                            <button class="admin-btn danger" type="button" wire:click="delete({{ $brand->id }})" wire:confirm="Yakin ingin menghapus brand ini?">
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:32px;opacity:.5;">
                            Belum ada brand.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    (function () {
        function initBrandSort() {
            const el = document.getElementById('brandsSortable');
            if (!el || el._sortable) return;

            el._sortable = new Sortable(el, {
                handle: '.sort-handle',
                animation: 150,
                onEnd() {
                    const rows = el.querySelectorAll('tr[data-id]');
                    const ordered = Array.from(rows).map((row, index) => ({
                        id: parseInt(row.dataset.id),
                        order: index + 1,
                    }));
                    Livewire.dispatch('updateSortOrder', { orderedIds: ordered });
                }
            });
        }

        document.addEventListener('DOMContentLoaded', initBrandSort);
        document.addEventListener('livewire:navigated', initBrandSort);
    })();
</script>
@endpush