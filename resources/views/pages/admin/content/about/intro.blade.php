<?php

use App\Models\AboutSection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.admin')]
#[Title('About Intro - Admin Compify')]
class extends Component {
    use WithPagination;

    public int $perPage = 10;

    public ?int $editingId = null;
    public string $description = '';
    public string $button_text = '';
    public string $button_url = '';
    public bool $is_active = true;
    public int $sort_order = 0;

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function sections()
    {
        return AboutSection::type(AboutSection::TYPE_INTRO)
            ->orderBy('sort_order')
            ->latest()
            ->paginate($this->perPage);
    }

    public function save(): void
    {
        $this->validate([
            'description' => ['required', 'string'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $payload = [
            'section_type' => AboutSection::TYPE_INTRO,
            'description' => $this->description,
            'button_text' => $this->button_text ?: null,
            'button_url' => $this->button_url ?: null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->editingId) {
            AboutSection::findOrFail($this->editingId)->update($payload);
        } else {
            AboutSection::create($payload);
        }

        session()->flash('success', 'About intro berhasil disimpan.');
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $section = AboutSection::findOrFail($id);

        $this->editingId = $section->id;
        $this->description = $section->description ?? '';
        $this->button_text = $section->button_text ?? '';
        $this->button_url = $section->button_url ?? '';
        $this->is_active = (bool) $section->is_active;
        $this->sort_order = $section->sort_order ?? 0;
    }

    public function delete(int $id): void
    {
        AboutSection::findOrFail($id)->delete();

        session()->flash('success', 'About intro berhasil dihapus.');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->description = '';
        $this->button_text = '';
        $this->button_url = '';
        $this->is_active = true;
        $this->sort_order = 0;
        $this->resetValidation();
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <h2>About Intro Manager</h2>
        <p>Mengatur deskripsi utama dan tombol pada halaman About.</p>
    </div>

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="admin-panel-v2 admin-form">
        <h2>{{ $editingId ? 'Edit About Intro' : 'Tambah About Intro' }}</h2>

        <label>
            Deskripsi
            <textarea wire:model="description" rows="5" placeholder="Tulis deskripsi utama About Page"></textarea>
            @error('description') <span class="error-text">{{ $message }}</span> @enderror
        </label>

        <div class="admin-grid">
            <label>
                Teks Tombol
                <input type="text" wire:model="button_text" placeholder="Belanja Sekarang">
                @error('button_text') <span class="error-text">{{ $message }}</span> @enderror
            </label>

            <label>
                URL Tombol
                <input type="text" wire:model="button_url" placeholder="/products">
                @error('button_url') <span class="error-text">{{ $message }}</span> @enderror
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
            <h2>Data About Intro</h2>

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
                    <th>Deskripsi</th>
                    <th>Tombol</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->sections as $section)
                    <tr>
                        <td>{{ $section->sort_order }}</td>
                        <td>{{ str($section->description)->limit(90) }}</td>
                        <td>{{ $section->button_text ?: '-' }}</td>
                        <td>{{ $section->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td>
                            <button class="admin-btn" type="button" wire:click="edit({{ $section->id }})">Edit</button>
                            <button class="admin-btn danger" type="button" wire:click="delete({{ $section->id }})" wire:confirm="Yakin hapus about intro ini?">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Belum ada about intro.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="admin-pagination">
            {{ $this->sections->links() }}
        </div>
    </div>
</div>
