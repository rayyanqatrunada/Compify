<?php

use App\Models\EventSetting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('components.layouts.admin')]
#[Title('Atur Event - Compify')]
class extends Component
{
    public ?EventSetting $event = null;

    public string $title = 'Flash Sale';
    public ?string $subtitle = 'berakhir dalam';
    public bool $is_active = false;
    public ?string $starts_at = null;
    public ?string $ends_at = null;

    public string $show_hero_section = '1';
    public string $show_flash_sale_section = '1';
    public string $show_full_banner_section = '1';
    public string $show_combo_package_section = '1';

    public function mount(): void
    {
        $this->event = EventSetting::query()->firstOrCreate([], [
            'title' => 'Flash Sale',
            'subtitle' => 'berakhir dalam',
            'is_active' => false,

            'show_hero_section' => true,
            'show_flash_sale_section' => true,
            'show_full_banner_section' => true,
            'show_combo_package_section' => true,
        ]);

        $this->title = $this->event->title ?: 'Flash Sale';
        $this->subtitle = $this->event->subtitle;
        $this->is_active = (bool) $this->event->is_active;
        $this->starts_at = $this->event->starts_at?->format('Y-m-d\TH:i');
        $this->ends_at = $this->event->ends_at?->format('Y-m-d\TH:i');

        $this->show_hero_section = $this->event->show_hero_section ? '1' : '0';
        $this->show_flash_sale_section = $this->event->show_flash_sale_section ? '1' : '0';
        $this->show_full_banner_section = $this->event->show_full_banner_section ? '1' : '0';
        $this->show_combo_package_section = $this->event->show_combo_package_section ? '1' : '0';
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:160'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],

            'show_hero_section' => ['required', 'in:0,1'],
            'show_flash_sale_section' => ['required', 'in:0,1'],
            'show_full_banner_section' => ['required', 'in:0,1'],
            'show_combo_package_section' => ['required', 'in:0,1'],
        ]);

        $this->event->update([
            'title' => $this->title,
            'subtitle' => $this->subtitle ?: null,
            'is_active' => $this->is_active,
            'starts_at' => $this->starts_at ?: null,
            'ends_at' => $this->ends_at ?: null,

            'show_hero_section' => $this->show_hero_section === '1',
            'show_flash_sale_section' => $this->show_flash_sale_section === '1',
            'show_full_banner_section' => $this->show_full_banner_section === '1',
            'show_combo_package_section' => $this->show_combo_package_section === '1',
        ]);

        $this->event->refresh();

        session()->flash('success', 'Pengaturan event berhasil disimpan.');
    }
};
?>

<div class="admin-page-v2 admin-event-page-v2">
    <div class="admin-section-title-v2">
        <div>
            <h2>Atur Event</h2>
            <p>Mengatur status event, waktu mulai, waktu berakhir, dan section yang tampil di halaman event.</p>
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
                <span>Judul Event</span>
                <input type="text" wire:model="title" placeholder="Flash Sale">
                @error('title')
                    <small>{{ $message }}</small>
                @enderror
            </label>

            <label>
                <span>Subtitle Countdown</span>
                <input type="text" wire:model="subtitle" placeholder="berakhir dalam">
                @error('subtitle')
                    <small>{{ $message }}</small>
                @enderror
            </label>

            <label>
                <span>Mulai Event</span>
                <input type="datetime-local" wire:model="starts_at">
                @error('starts_at')
                    <small>{{ $message }}</small>
                @enderror
            </label>

            <label>
                <span>Berakhir Event</span>
                <input type="datetime-local" wire:model="ends_at">
                @error('ends_at')
                    <small>{{ $message }}</small>
                @enderror
            </label>
        </div>

        <label class="admin-check-v2">
            <input type="checkbox" wire:model="is_active">
            <span>Aktifkan event</span>
        </label>

        <div class="admin-help-v2">
            Event hanya tampil di halaman shop jika status aktif dan waktu sekarang berada di antara waktu mulai dan waktu berakhir.
        </div>

        <br>

        <div class="admin-panel-v2" style="box-shadow:none; padding: 0;">
            <h3>Section yang Ditampilkan</h3>

            <div class="admin-grid-v2 admin-grid-v2--event-settings">
                <label>
                    <span>Hero Event</span>
                    <select wire:model="show_hero_section">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                    @error('show_hero_section')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <label>
                    <span>Flash Sale</span>
                    <select wire:model="show_flash_sale_section">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                    @error('show_flash_sale_section')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <label>
                    <span>Full Banner Event</span>
                    <select wire:model="show_full_banner_section">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                    @error('show_full_banner_section')
                        <small>{{ $message }}</small>
                    @enderror
                </label>

                <label>
                    <span>Paket Bundling</span>
                    <select wire:model="show_combo_package_section">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                    @error('show_combo_package_section')
                        <small>{{ $message }}</small>
                    @enderror
                </label>
            </div>

            {{-- <div class="admin-help-v2">
                Jika section dinonaktifkan, section tersebut benar-benar hilang dari halaman event. Data flash sale, banner, dan paket tetap aman di admin.
            </div> --}}
        </div>

        <div class="admin-actions-v2">
            <button type="submit" class="admin-btn-v2 admin-btn-v2--primary">
                Simpan Pengaturan
            </button>
        </div>
    </form>
</div>