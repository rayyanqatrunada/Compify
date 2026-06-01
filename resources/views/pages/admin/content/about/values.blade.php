<?php

use App\Models\AboutSection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.admin')]
#[Title('About Values - Admin Compify')]
class extends Component {
    use WithPagination;

    public int $perPage = 10;

    public ?int $editingId = null;
    public string $title = '';
    public string $description = '';
    public string $icon = '';
    public bool $is_active = true;
    public int $sort_order = 0;

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function sections()
    {
        return AboutSection::type(AboutSection::TYPE_VALUE)
            ->orderBy('sort_order')
            ->latest()
            ->paginate($this->perPage);
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $payload = [
            'section_type' => AboutSection::TYPE_VALUE,
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->icon ?: null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->editingId) {
            AboutSection::findOrFail($this->editingId)->update($payload);
        } else {
            AboutSection::create($payload);
        }

        session()->flash('success', 'About value berhasil disimpan.');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $section = AboutSection::findOrFail($id);

        $this->editingId = $section->id;
        $this->title = $section->title ?? '';
        $this->description = $section->description ?? '';
        $this->icon = $section->icon ?? '';
        $this->is_active = (bool) $section->is_active;
        $this->sort_order = $section->sort_order ?? 0;
    }

    public function delete(int $id): void
    {
        AboutSection::findOrFail($id)->delete();

        session()->flash('success', 'About value berhasil dihapus.');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->description = '';
        $this->icon = '';
        $this->is_active = true;
        $this->sort_order = 0;
        $this->resetValidation();
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <h2>About Values Manager</h2>
        <p>Mengatur kartu nilai perusahaan pada halaman About.</p>
    </div>

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="admin-panel-v2 admin-form">
        <h2>{{ $editingId ? 'Edit About Value' : 'Tambah About Value' }}</h2>

        <div class="admin-grid">
            <label>
                Judul
                <input type="text" wire:model="title" placeholder="Keaslian">
                @error('title') <span class="error-text">{{ $message }}</span> @enderror
            </label>

            <label>
                Icon
                <input type="text" wire:model="icon" placeholder="OK">
                @error('icon') <span class="error-text">{{ $message }}</span> @enderror
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

        <label>
            Deskripsi
            <textarea wire:model="description" rows="4" placeholder="Tulis deskripsi value"></textarea>
            @error('description') <span class="error-text">{{ $message }}</span> @enderror
        </label>

        <div class="admin-actions">
            <button class="admin-btn" type="submit">{{ $editingId ? 'Update' : 'Simpan' }}</button>
            <button class="admin-btn secondary" type="button" wire:click="resetForm">Reset</button>
        </div>
    </form>

    <div class="admin-panel-v2">
        <div class="admin-table-head">
            <h2>Data About Values</h2>

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
                    <th>Icon</th>
                    <th>Judul</th>
                    <th>Deskripsi</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->sections as $section)
                    <tr>
                        <td>{{ $section->sort_order }}</td>
                        <td>{{ $section->icon ?: '-' }}</td>
                        <td>{{ $section->title ?? '-' }}</td>
                        <td>{{ str($section->description)->limit(80) }}</td>
                        <td>{{ $section->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td>
                            <button class="admin-btn" type="button" wire:click="edit({{ $section->id }})">Edit</button>
                            <button class="admin-btn danger" type="button" wire:click="delete({{ $section->id }})" wire:confirm="Yakin hapus about value ini?">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Belum ada about values.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="admin-pagination">
            {{ $this->sections->links() }}
        </div>
    </div>
</div>
