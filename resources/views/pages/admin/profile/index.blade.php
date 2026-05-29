<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('components.layouts.admin')]
#[Title('Profil Admin - Compify')]
class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $email = '';

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public $avatarFile = null;
    public ?string $currentAvatar = null;

    public function mount(): void
    {
        $admin = Auth::guard('admin')->user();

        abort_if(! $admin, 403);

        $this->name = $admin->name ?? '';
        $this->email = $admin->email ?? '';
        $this->currentAvatar = $admin->avatar ?? null;
    }

    public function saveProfile(): void
    {
        $admin = Auth::guard('admin')->user();

        abort_if(! $admin, 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($admin->id),
            ],
            'avatarFile' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($this->avatarFile) {
            if ($admin->avatar && Storage::disk('public')->exists($admin->avatar)) {
                Storage::disk('public')->delete($admin->avatar);
            }

            $admin->avatar = $this->avatarFile->store('admins/avatar', 'public');
        }

        $admin->name = $data['name'];
        $admin->email = $data['email'];
        $admin->save();

        $this->currentAvatar = $admin->fresh()->avatar;
        $this->avatarFile = null;

        session()->flash('success', 'Profil admin berhasil diperbarui.');
    }

    public function savePassword(): void
    {
        $admin = Auth::guard('admin')->user();

        abort_if(! $admin, 403);

        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($this->current_password, $admin->password)) {
            $this->addError('current_password', 'Password lama tidak sesuai.');
            return;
        }

        $admin->password = Hash::make($this->password);
        $admin->save();

        $this->current_password = '';
        $this->password = '';
        $this->password_confirmation = '';

        session()->flash('success', 'Password admin berhasil diperbarui.');
    }
};
?>

<div class="admin-page-v2">
    <div class="admin-section-title-v2">
        <div>
            <h2>Profil Admin</h2>
            <p>Kelola data akun admin, foto profil, email, dan password.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="flash-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="admin-profile-layout">
        <section class="admin-panel-v2 admin-profile-card">
            <div class="admin-profile-preview">
                <div class="admin-profile-big-avatar">
                    @if($avatarFile)
                        <img src="{{ $avatarFile->temporaryUrl() }}" alt="Preview">
                    @elseif($currentAvatar)
                        <img src="{{ Storage::url($currentAvatar) }}" alt="{{ $name }}">
                    @else
                        <span>{{ strtoupper(substr($name ?: 'A', 0, 1)) }}</span>
                    @endif
                </div>

                <h3>{{ $name ?: 'Admin Compify' }}</h3>
                <p>{{ $email }}</p>
            </div>

            <form wire:submit="saveProfile" class="admin-form">
                <div class="admin-grid">
                    <label>
                        Nama Admin
                        <input type="text" wire:model="name">
                        @error('name') <small class="error-text">{{ $message }}</small> @enderror
                    </label>

                    <label>
                        Email Admin
                        <input type="email" wire:model="email">
                        @error('email') <small class="error-text">{{ $message }}</small> @enderror
                    </label>

                    <label>
                        Foto Profil
                        <input type="file" wire:model="avatarFile" accept="image/*">
                        @error('avatarFile') <small class="error-text">{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="admin-actions">
                    <button type="submit" class="admin-btn">
                        Simpan Profil
                    </button>
                </div>
            </form>
        </section>

        <section class="admin-panel-v2">
            <h2>Ubah Password</h2>

            <form wire:submit="savePassword" class="admin-form">
                <label>
                    Password Lama
                    <input type="password" wire:model="current_password">
                    @error('current_password') <small class="error-text">{{ $message }}</small> @enderror
                </label>

                <label>
                    Password Baru
                    <input type="password" wire:model="password">
                    @error('password') <small class="error-text">{{ $message }}</small> @enderror
                </label>

                <label>
                    Konfirmasi Password Baru
                    <input type="password" wire:model="password_confirmation">
                </label>

                <div class="admin-actions">
                    <button type="submit" class="admin-btn">
                        Update Password
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>