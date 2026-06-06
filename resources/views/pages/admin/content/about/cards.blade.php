<?php

use App\Models\AboutSection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.admin')]
#[Title('About Cards - Admin Compify')]
class extends Component {
    use WithPagination;

    public int $perPage = 10;
    public string $activeTab = 'stats';

    public ?int $editingId = null;
    public string $title = '';
    public string $description = '';
    public string $stat_value = '';
    public string $icon = '';
    public bool $is_active = true;
    public int $sort_order = 0;

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetForm();
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    private function activeType(): string
    {
        return $this->activeTab === 'stats'
            ? AboutSection::TYPE_STATS
            : AboutSection::TYPE_VALUE;
    }

    #[Computed]
    public function sections()
    {
        return AboutSection::type($this->activeType())
            ->orderBy('sort_order')
            ->latest()
            ->paginate($this->perPage);
    }

    public function save(): void
    {
        $rules = [
            'title'      => ['required', 'string', 'max:255'],
            'is_active'  => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];

        if ($this->activeTab === 'stats') {
            $rules['stat_value'] = ['required', 'string', 'max:50'];
        }

        if ($this->activeTab === 'values') {
            $rules['description'] = ['required', 'string'];
            $rules['icon']        = ['nullable', 'string', 'max:50'];
        }

        $this->validate($rules);

        $payload = [
            'section_type' => $this->activeType(),
            'title'        => $this->title,
            'is_active'    => $this->is_active,
            'sort_order'   => $this->sort_order,
        ];

        if ($this->activeTab === 'stats') {
            $payload['stat_value'] = $this->stat_value;
        }

        if ($this->activeTab === 'values') {
            $payload['description'] = $this->description;
            $payload['icon']        = $this->icon ?: null;
        }

        if ($this->editingId) {
            AboutSection::findOrFail($this->editingId)->update($payload);
        } else {
            AboutSection::create($payload);
        }

        session()->flash('success', 'Data berhasil disimpan.');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $section = AboutSection::findOrFail($id);

        $this->editingId   = $section->id;
        $this->title       = $section->title ?? '';
        $this->description = $section->description ?? '';
        $this->stat_value  = $section->stat_value ?? '';
        $this->icon        = $section->icon ?? '';
        $this->is_active   = (bool) $section->is_active;
        $this->sort_order  = $section->sort_order ?? 0;
    }

    public function delete(int $id): void
    {
        AboutSection::findOrFail($id)->delete();

        session()->flash('success', 'Data berhasil dihapus.');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editingId   = null;
        $this->title       = '';
        $this->description = '';
        $this->stat_value  = '';
        $this->icon        = '';
        $this->is_active   = true;
        $this->sort_order  = 0;
        $this->resetValidation();
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <h2>About Cards Manager</h2>
        <p>Mengatur Stats dan Values pada halaman About.</p>
    </div>

    {{-- Tab Switch --}}
    <div class="admin-tabs-v2" style="display:flex;gap:8px;margin-bottom:24px;">
        <button
            class="admin-btn {{ $activeTab === 'stats' ? '' : 'secondary' }}"
            type="button"
            wire:click="switchTab('stats')"
        >Stats</button>

        <button
            class="admin-btn {{ $activeTab === 'values' ? '' : 'secondary' }}"
            type="button"
            wire:click="switchTab('values')"
        >Values</button>
    </div>

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="admin-panel-v2 admin-form">
        <h2>{{ $editingId ? 'Edit' : 'Tambah' }} About {{ ucfirst($activeTab) }}</h2>

        <div class="admin-grid">
            @if($activeTab === 'stats')
                <label>
                    Nilai Statistik
                    <input type="text" wire:model="stat_value" placeholder="99+">
                    @error('stat_value') <span class="error-text">{{ $message }}</span> @enderror
                </label>
            @endif

            <label>
                {{ $activeTab === 'stats' ? 'Label' : 'Judul' }}
                <input type="text" wire:model="title" placeholder="{{ $activeTab === 'stats' ? 'Produk' : 'Keaslian' }}">
                @error('title') <span class="error-text">{{ $message }}</span> @enderror
            </label>

            @if($activeTab === 'values')
                <label>
                    Icon
                    <input type="text" wire:model="icon" placeholder="OK">
                    @error('icon') <span class="error-text">{{ $message }}</span> @enderror
                </label>
            @endif

            <label>
                Urutan
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
        </div>

        @if($activeTab === 'values')
            <label>
                Deskripsi
                <textarea wire:model="description" rows="4" placeholder="Tulis deskripsi value..."></textarea>
                @error('description') <span class="error-text">{{ $message }}</span> @enderror
            </label>
        @endif

        <div class="admin-actions">
            <button class="admin-btn" type="submit">{{ $editingId ? 'Update' : 'Simpan' }}</button>
            <button class="admin-btn secondary" type="button" wire:click="resetForm">Reset</button>
        </div>
    </form>

    <div class="admin-panel-v2">
        <div class="admin-table-head">
            <h2>Data About {{ ucfirst($activeTab) }}</h2>

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
                    @if($activeTab === 'stats')
                        <th>Nilai</th>
                    @endif
                    @if($activeTab === 'values')
                        <th>Icon</th>
                    @endif
                    <th>{{ $activeTab === 'stats' ? 'Label' : 'Judul' }}</th>
                    @if($activeTab === 'values')
                        <th>Deskripsi</th>
                    @endif
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->sections as $section)
                    <tr>
                        <td>{{ $section->sort_order }}</td>
                        @if($activeTab === 'stats')
                            <td>{{ $section->stat_value ?? '-' }}</td>
                        @endif
                        @if($activeTab === 'values')
                            <td>{{ $section->icon ?: '-' }}</td>
                        @endif
                        <td>{{ $section->title ?? '-' }}</td>
                        @if($activeTab === 'values')
                            <td>{{ str($section->description)->limit(80) }}</td>
                        @endif
                        <td>{{ $section->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td>
                            <button class="admin-btn" type="button" wire:click="edit({{ $section->id }})">Edit</button>
                            <button class="admin-btn danger" type="button" wire:click="delete({{ $section->id }})" wire:confirm="Yakin hapus data ini?">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Belum ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="admin-pagination">
            {{ $this->sections->links() }}
        </div>
    </div>
</div>