<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
#[Layout('layouts.shop')]
#[Title('Akun Saya - Compify')]
class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public ?string $phone = null;
    public ?string $address = null;
    public ?string $city = null;
    public ?string $province = null;
    public ?string $postal_code = null;
    public ?string $gender = null;
    public ?string $birth_date = null;

    public string $password = '';
    public string $password_confirmation = '';

    public $avatarFile = null;
    public ?string $currentAvatar = null;

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->address = $user->address;
        $this->city = $user->city;
        $this->province = $user->province;
        $this->postal_code = $user->postal_code;
        $this->gender = $user->gender;
        $this->birth_date = $user->birth_date ? $user->birth_date->format('Y-m-d') : null;
        $this->currentAvatar = $user->avatar;
    }

    public function saveProfile(): void
    {
        $user = auth()->user();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'birth_date' => ['nullable', 'date'],
            'avatarFile' => ['nullable', 'image', 'max:2048'],
        ]);

        unset($data['avatarFile']);

        if ($this->avatarFile) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $data['avatar'] = $this->avatarFile->store('avatars', 'public');
        }

        $user->update($data);

        $this->currentAvatar = $user->fresh()->avatar;
        $this->avatarFile = null;

        session()->flash('success', 'Profil berhasil diperbarui.');
    }

    public function savePassword(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        auth()->user()->update([
            'password' => Hash::make($this->password),
        ]);

        $this->password = '';
        $this->password_confirmation = '';

        session()->flash('success', 'Password berhasil diperbarui.');
    }
};
?>

<section class="account-page">
    <div class="account-layout">
        <aside class="account-sidebar">
            <div class="account-avatar">
                @if($avatarFile)
                    <img src="{{ $avatarFile->temporaryUrl() }}" alt="Avatar Preview">
                @elseif($currentAvatar)
                    <img src="{{ Storage::url($currentAvatar) }}" alt="{{ $name }}">
                @else
                    <span>{{ strtoupper(substr($name, 0, 1)) }}</span>
                @endif

                <label>
                    ✎
                    <input type="file" wire:model="avatarFile" accept="image/*">
                </label>
            </div>

            <h2>{{ $name }}</h2>
            <p>{{ auth()->user()->role === 'admin' ? 'Admin' : 'Customer' }}</p>

            <nav>
                <a href="#personal">Personal Information</a>
                <a href="#password">Login & Password</a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Log Out</button>
                </form>
            </nav>
        </aside>

        <main class="account-panel">
            @if(session('success'))
                <div class="flash-success">{{ session('success') }}</div>
            @endif

            <form wire:submit="saveProfile" id="personal" class="account-form">
                <h1>Personal Information</h1>

                <div class="account-radio-row">
                    <label>
                        <input type="radio" wire:model="gender" value="male">
                        Male
                    </label>

                    <label>
                        <input type="radio" wire:model="gender" value="female">
                        Female
                    </label>
                </div>

                <div class="account-grid">
                    <label>
                        Nama Lengkap
                        <input type="text" wire:model.defer="name">
                    </label>

                    <label>
                        Email
                        <input type="email" wire:model.defer="email">
                    </label>

                    <label>
                        Nomor HP
                        <input type="text" wire:model.defer="phone">
                    </label>

                    <label>
                        Tanggal Lahir
                        <input type="date" wire:model.defer="birth_date">
                    </label>

                    <label class="span-2">
                        Alamat
                        <input type="text" wire:model.defer="address">
                    </label>

                    <label>
                        Kota
                        <input type="text" wire:model.defer="city">
                    </label>

                    <label>
                        Provinsi
                        <input type="text" wire:model.defer="province">
                    </label>

                    <label>
                        Kode Pos
                        <input type="text" wire:model.defer="postal_code">
                    </label>
                </div>

                <div class="account-actions">
                    <button type="button" wire:click="$refresh" class="outline">Discard Changes</button>
                    <button type="submit">Save Changes</button>
                </div>
            </form>

            <form wire:submit="savePassword" id="password" class="account-form password-form">
                <h1>Login & Password</h1>

                <div class="account-grid">
                    <label>
                        Password Baru
                        <input type="password" wire:model.defer="password">
                    </label>

                    <label>
                        Konfirmasi Password
                        <input type="password" wire:model.defer="password_confirmation">
                    </label>
                </div>

                <div class="account-actions">
                    <button type="submit">Update Password</button>
                </div>
            </form>
        </main>
    </div>
</section>