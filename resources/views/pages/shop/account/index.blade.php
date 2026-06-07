<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('components.layouts.shop')]
#[Title('Profil Saya - Compify')]
class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $city = '';
    public string $province = '';
    public string $postal_code = '';

    public $avatarFile = null;
    public ?string $currentAvatar = null;

    public function mount(): void
    {
        $user = Auth::guard('customer')->user();

        abort_if(! $user, 403);

        $this->name = $user->name ?? '';
        $this->username = $user->username ?? '';
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        $this->address = $user->address ?? '';
        $this->city = $user->city ?? '';
        $this->province = $user->province ?? '';
        $this->postal_code = $user->postal_code ?? '';
        $this->currentAvatar = $user->avatar;
    }

    public function save(): void
    {
        $user = Auth::guard('customer')->user();

        abort_if(! $user, 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'nullable',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'avatarFile' => ['nullable', 'image', 'max:2048'],
        ]);

        unset($data['avatarFile']);

        if ($this->avatarFile) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $data['avatar'] = $this->avatarFile->store('customers/avatar', 'public');
        }

        $user->update($data);

        $this->currentAvatar = $user->fresh()->avatar;
        $this->avatarFile = null;

        session()->flash('success', 'Profil berhasil diperbarui.');
    }
};
?>

<div class="customer-account-page">

    @if(session('success'))
        <div class="account-alert-success">
            {{ session('success') }}
        </div>
    @endif

    <section class="account-card">
        <div class="account-profile-side">
            <div class="account-avatar-preview">
                @if($avatarFile)
                    <img src="{{ $avatarFile->temporaryUrl() }}" alt="Avatar Preview">
                @elseif($currentAvatar)
                    <img src="{{ Storage::url($currentAvatar) }}" alt="Avatar">
                @else
                    <span>{{ strtoupper(substr($name ?: 'C', 0, 1)) }}</span>
                @endif
            </div>

            <h2>{{ $username ?: $name }}</h2>
            <p>{{ $email }}</p>

            <label class="account-upload-btn">
                Ganti Foto
                <input type="file" wire:model="avatarFile" accept="image/*">
            </label>

            @error('avatarFile')
                <small class="account-error">{{ $message }}</small>
            @enderror
        </div>

        <form wire:submit="save" class="account-form">
            <div class="account-form-grid">
                <label>
                    Nama Lengkap
                    <input type="text" wire:model="name">
                    @error('name') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    Username
                    <input type="text" wire:model="username" placeholder="">
                    @error('username') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    Email
                    <input type="email" wire:model="email">
                    @error('email') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    Nomor HP
                    <input type="text" wire:model="phone" placeholder="Opsional">
                    @error('phone') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    Kota
                    <input type="text" wire:model="city" placeholder="Opsional">
                    @error('city') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    Provinsi
                    <input type="text" wire:model="province" placeholder="Opsional">
                    @error('province') <small>{{ $message }}</small> @enderror
                </label>

                <label>
                    Kode Pos
                    <input type="text" wire:model="postal_code" placeholder="Opsional">
                    @error('postal_code') <small>{{ $message }}</small> @enderror
                </label>

                <label class="account-full-field">
                    Alamat Lengkap
                    <textarea wire:model="address" rows="4" placeholder="Opsional"></textarea>
                    @error('address') <small>{{ $message }}</small> @enderror
                </label>
            </div>

            <div class="account-actions">
                <form method="POST" action="{{ route('customer.logout') }}" class="account-logout-area">
                    @csrf

                    <button type="submit" class="account-logout-btn">
                        Keluar dari Akun
                    </button>
                </form>

                <a href="{{ route('home') }}" wire:navigate>Kembali</a>
                <button class="account-submit-btn" type="submit">Simpan Perubahan</button>
            </div>
        </form>

    </section>
</div>