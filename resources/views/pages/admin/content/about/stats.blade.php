<?php

use App\Models\AboutSection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.admin')]
#[Title('About Stats - Admin Compify')]
class extends Component {
    use WithPagination;

    public int $perPage = 10;

    public ?int $editingId = null;
    public string $title = '';
    public string $stat_value = '';
    public bool $is_active = true;
    public int $sort_order = 0;

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function sections()
    {
        return AboutSection::type(AboutSection::TYPE_STATS)
            ->orderBy('sort_order')
            ->latest()
            ->paginate($this->perPage);
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'stat_value' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $payload = [
            'section_type' => AboutSection::TYPE_STATS,
            'title' => $this->title,
            'stat_value' => $this->stat_value,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->editingId) {
            AboutSection::findOrFail($this->editingId)->update($payload);
        } else {
            AboutSection::create($payload);
        }

        session()->flash('success', 'About stats berhasil disimpan.');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $section = AboutSection::findOrFail($id);

        $this->editingId = $section->id;
        $this->title = $section->title ?? '';
        $this->stat_value = $section->stat_value ?? '';
        $this->is_active = (bool) $section->is_active;
        $this->sort_order = $section->sort_order ?? 0;
    }

    public function delete(int $id): void
    {
        AboutSection::findOrFail($id)->delete();

        session()->flash('success', 'About stats berhasil dihapus.');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->stat_value = '';
        $this->is_active = true;
        $this->sort_order = 0;
        $this->resetValidation();
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <h2>About Stats Manager</h2>
        <p>Mengatur kartu track record pada halaman About.</p>
    </div>

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="admin-panel-v2 admin-form">
        <h2>{{ $editingId ? 'Edit About Stat' : 'Tambah About Stat' }}</h2>

        <div class="admin-grid">
            <label>
                Nilai Statistik
                <input type="text" wire:model="stat_value" placeholder="99+">
                @error('stat_value') <span class="error-text">{{ $message }}</span> @enderror
            </label>

            <label>
                Label
                <input type="text" wire:model="title" placeholder="Produk">
                @error('title') <span class="error-text">{{ $message }}</span> @enderror
            </label>

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

        <div class="admin-actions">
            <button class="admin-btn" type="submit">{{ $editingId ? 'Update' : 'Simpan' }}</button>
            <button class="admin-btn secondary" type="button" wire:click="resetForm">Reset</button>
        </div>
    </form>

    <div class="admin-panel-v2">
        <div class="admin-table-head">
            <h2>Data About Stats</h2>

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
                    <th>Nilai</th>
                    <th>Label</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->sections as $section)
                    <tr>
                        <td>{{ $section->sort_order }}</td>
                        <td>{{ $section->stat_value ?? '-' }}</td>
                        <td>{{ $section->title ?? '-' }}</td>
                        <td>{{ $section->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td>
                            <button class="admin-btn" type="button" wire:click="edit({{ $section->id }})">Edit</button>
                            <button class="admin-btn danger" type="button" wire:click="delete({{ $section->id }})" wire:confirm="Yakin hapus about stat ini?">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Belum ada about stats.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="admin-pagination">
            {{ $this->sections->links() }}
        </div>
    </div>
</div>
