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

    public function mount(): void
    {
        $this->event = EventSetting::query()->firstOrCreate([], [
            'title' => 'Flash Sale',
            'subtitle' => 'berakhir dalam',
            'is_active' => false,
        ]);

        $this->title = $this->event->title;
        $this->subtitle = $this->event->subtitle;
        $this->is_active = (bool) $this->event->is_active;
        $this->starts_at = $this->event->starts_at?->format('Y-m-d\TH:i');
        $this->ends_at = $this->event->ends_at?->format('Y-m-d\TH:i');
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:160'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $this->event->update($data);

        session()->flash('success', 'Pengaturan event berhasil disimpan.');
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <div>
            <h2>Atur Event</h2>
            <p>Mengatur status event, waktu mulai, dan waktu berakhir.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="admin-alert-v2 admin-alert-v2--success">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="admin-panel-v2 admin-form-v2">
        <div class="admin-grid-v2 admin-grid-v2--2">
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

        <div class="admin-actions-v2">
            <button type="submit" class="admin-btn-v2 admin-btn-v2--primary">
                Simpan Pengaturan
            </button>
        </div>
    </form>
</div>