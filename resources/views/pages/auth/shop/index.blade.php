<?php

use App\Models\ShopSetting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts.admin')]
#[Title('Shop Settings - Admin Compify')]
class extends Component {
    use WithFileUploads;

    public string $site_name = 'Compify';
    public ?string $support_email = null;
    public ?string $support_phone = null;

    public string $login_heading = 'Admin Sign In';
    public string $login_subheading = 'Masuk ke dashboard Compify';
    public string $login_showcase_title = 'Manage your store beautifully';
    public string $login_showcase_text = 'Kelola produk, banner, kategori, dan seluruh tampilan toko dari satu dashboard.';

    public $login_image;
    public ?string $current_login_image = null;

    public function mount(): void
    {
        $setting = ShopSetting::firstOrCreate(
            ['id' => 1],
            [
                'site_name' => 'Compify',
                'login_heading' => 'Admin Sign In',
                'login_subheading' => 'Masuk ke dashboard Compify',
                'login_showcase_title' => 'Manage your store beautifully',
                'login_showcase_text' => 'Kelola produk, banner, kategori, dan seluruh tampilan toko dari satu dashboard.',
            ]
        );

        $this->site_name = $setting->site_name;
        $this->support_email = $setting->support_email;
        $this->support_phone = $setting->support_phone;
        $this->login_heading = $setting->login_heading;
        $this->login_subheading = $setting->login_subheading;
        $this->login_showcase_title = $setting->login_showcase_title;
        $this->login_showcase_text = $setting->login_showcase_text;
        $this->current_login_image = $setting->login_image;
    }

    public function save(): void
    {
        $data = $this->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:255'],
            'login_heading' => ['required', 'string', 'max:255'],
            'login_subheading' => ['required', 'string', 'max:255'],
            'login_showcase_title' => ['required', 'string', 'max:255'],
            'login_showcase_text' => ['nullable', 'string'],
            'login_image' => ['nullable', 'image', 'max:2048'],
        ]);

        $setting = ShopSetting::firstOrCreate(['id' => 1]);

        if ($this->login_image) {
            $data['login_image'] = $this->login_image->store('settings', 'public');
        } else {
            unset($data['login_image']);
        }

        $setting->update($data);

        $this->current_login_image = $setting->fresh()->login_image;

        session()->flash('success', 'Shop settings berhasil disimpan.');
    }
};
?>

<div>
    <div class="admin-page-head">
        <div>
            <p>Settings</p>
            <h2>Shop Settings</h2>
        </div>
    </div>

    @if (session('success'))
        <div class="flash-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="admin-panel">
        <form wire:submit="save" class="admin-form">
            <div class="settings-grid">
                <div class="settings-column">
                    <h3>General</h3>

                    <label>
                        Nama Toko
                        <input type="text" wire:model.defer="site_name">
                    </label>

                    <label>
                        Support Email
                        <input type="email" wire:model.defer="support_email">
                    </label>

                    <label>
                        Support Phone
                        <input type="text" wire:model.defer="support_phone">
                    </label>
                </div>

                <div class="settings-column">
                    <h3>Admin Login Content</h3>

                    <label>
                        Login Heading
                        <input type="text" wire:model.defer="login_heading">
                    </label>

                    <label>
                        Login Subheading
                        <input type="text" wire:model.defer="login_subheading">
                    </label>

                    <label>
                        Showcase Title
                        <input type="text" wire:model.defer="login_showcase_title">
                    </label>

                    <label>
                        Showcase Text
                        <textarea rows="5" wire:model.defer="login_showcase_text"></textarea>
                    </label>

                    <label>
                        Login Image / Artwork
                        <input type="file" wire:model="login_image" accept="image/*">
                    </label>

                    <div class="settings-image-preview">
                        @if ($login_image)
                            <img src="{{ $login_image->temporaryUrl() }}" alt="Preview login image">
                        @elseif ($current_login_image)
                            <img src="{{ asset('storage/' . $current_login_image) }}" alt="Current login image">
                        @else
                            <div class="settings-image-placeholder">Belum ada gambar login.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="admin-actions">
                <button type="submit" class="admin-btn">Simpan Settings</button>
            </div>
        </form>
    </div>
</div>