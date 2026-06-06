<?php

use App\Models\AboutSection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.admin')]
#[Title('About Content - Admin Compify')]
class extends Component {
    use WithPagination;

    public int $perPage = 10;
    public string $activeTab = 'intro';

    public ?int $editingId = null;
    public string $description = '';
    public string $year = '';
    public string $title = '';
    public string $button_text = '';
    public string $button_url = '';
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
        return match($this->activeTab) {
            'intro'   => AboutSection::TYPE_INTRO,
            'quote'   => AboutSection::TYPE_QUOTE,
            'history' => AboutSection::TYPE_HISTORY,
            default   => AboutSection::TYPE_INTRO,
        };
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
            'description' => ['required', 'string'],
            'is_active'   => ['boolean'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ];

        if ($this->activeTab === 'intro') {
            $rules['button_text'] = ['nullable', 'string', 'max:100'];
            $rules['button_url']  = ['nullable', 'string', 'max:255'];
        }

        if ($this->activeTab === 'history') {
            $rules['year']  = ['required', 'string', 'max:10'];
            $rules['title'] = ['required', 'string', 'max:255'];
        }

        $this->validate($rules);

        $payload = [
            'section_type' => $this->activeType(),
            'description'  => $this->description,
            'is_active'    => $this->is_active,
            'sort_order'   => $this->sort_order,
        ];

        if ($this->activeTab === 'intro') {
            $payload['button_text'] = $this->button_text ?: null;
            $payload['button_url']  = $this->button_url ?: null;
        }

        if ($this->activeTab === 'history') {
            $payload['year']  = $this->year;
            $payload['title'] = $this->title;
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
        $this->description = $section->description ?? '';
        $this->year        = $section->year ?? '';
        $this->title       = $section->title ?? '';
        $this->button_text = $section->button_text ?? '';
        $this->button_url  = $section->button_url ?? '';
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
        $this->description = '';
        $this->year        = '';
        $this->title       = '';
        $this->button_text = '';
        $this->button_url  = '';
        $this->is_active   = true;
        $this->sort_order  = 0;
        $this->resetValidation();
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <h2>About Content Manager</h2>
        <p>Mengatur Intro, Quote, dan History pada halaman About.</p>
    </div>

    {{-- Tab Switch --}}
    <div class="admin-tabs-v2" style="display:flex;gap:8px;margin-bottom:24px;">
        <button
            class="admin-btn {{ $activeTab === 'intro' ? '' : 'secondary' }}"
            type="button"
            wire:click="switchTab('intro')"
        >Intro</button>

        <button
            class="admin-btn {{ $activeTab === 'quote' ? '' : 'secondary' }}"
            type="button"
            wire:click="switchTab('quote')"
        >Quote</button>

        <button
            class="admin-btn {{ $activeTab === 'history' ? '' : 'secondary' }}"
            type="button"
            wire:click="switchTab('history')"
        >History</button>
    </div>

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="admin-panel-v2 admin-form">
        <h2>{{ $editingId ? 'Edit' : 'Tambah' }} About {{ ucfirst($activeTab) }}</h2>

        @if($activeTab === 'history')
            <div class="admin-grid">
                <label>
                    Tahun
                    <input type="text" wire:model="year" placeholder="2020">
                    @error('year') <span class="error-text">{{ $message }}</span> @enderror
                </label>

                <label>
                    Judul Kejadian
                    <input type="text" wire:model="title" placeholder="Compify didirikan">
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
        @endif

        <label>
            {{ $activeTab === 'history' ? 'Deskripsi Kejadian' : ($activeTab === 'quote' ? 'Quote' : 'Deskripsi') }}
            <textarea wire:model="description" rows="5" placeholder="{{ $activeTab === 'quote' ? 'Tulis quote perusahaan...' : ($activeTab === 'history' ? 'Ceritakan kejadian pada tahun ini...' : 'Tulis deskripsi utama About Page...') }}"></textarea>
            @error('description') <span class="error-text">{{ $message }}</span> @enderror
        </label>

        @if($activeTab === 'intro')
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
        @endif

        @if($activeTab === 'quote')
            <div class="admin-grid">
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
                    @if($activeTab === 'history')
                        <th>Tahun</th>
                        <th>Judul</th>
                    @endif
                    <th>{{ $activeTab === 'history' ? 'Deskripsi' : ($activeTab === 'quote' ? 'Quote' : 'Deskripsi') }}</th>
                    @if($activeTab === 'intro')
                        <th>Tombol</th>
                    @endif
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->sections as $section)
                    <tr>
                        <td>{{ $section->sort_order }}</td>
                        @if($activeTab === 'history')
                            <td>{{ $section->year ?? '-' }}</td>
                            <td>{{ $section->title ?? '-' }}</td>
                        @endif
                        <td>{{ str($section->description)->limit(80) }}</td>
                        @if($activeTab === 'intro')
                            <td>{{ $section->button_text ?: '-' }}</td>
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