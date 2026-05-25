<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.customer-auth')]
#[Title('Sign Up - Compify')]
class extends Component {
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $city = '';
    public string $province = '';
    public string $postal_code = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'city' => $data['city'],
            'province' => $data['province'],
            'postal_code' => $data['postal_code'],
            'password' => Hash::make($data['password']),
            'role' => 'customer',
        ]);

        Auth::guard('customer')->login($user);

        request()->session()->regenerate();

        $this->redirectRoute('home', navigate: true);
    }
};
?>

<section class="customer-auth-page">
    <div class="customer-auth-card register-mode">
        <div class="customer-auth-copy">
            <a href="{{ route('home') }}" class="product-back-button" wire:navigate>
                ← Kembali
            </a>

            <p>Create Account</p>
            <h1>Sign Up</h1>
            <span>
                Buat akun customer untuk menyimpan data pemesanan,
                wishlist, dan proses checkout di Compify.
            </span>
        </div>

        <form wire:submit="register" class="customer-auth-form">
            <h2>Daftar Akun</h2>

            <div class="customer-form-grid">
                <label>
                    Nama Lengkap
                    <input type="text" wire:model.defer="name" placeholder="Nama lengkap">
                    @error('name') <small class="error-text">{{ $message }}</small> @enderror
                </label>

                <label>
                    Email
                    <input type="email" wire:model.defer="email" placeholder="email@example.com">
                    @error('email') <small class="error-text">{{ $message }}</small> @enderror
                </label>

                <label>
                    Nomor HP
                    <input type="text" wire:model.defer="phone" placeholder="08xxxxxxxxxx">
                    @error('phone') <small class="error-text">{{ $message }}</small> @enderror
                </label>

                <label>
                    Kota
                    <input type="text" wire:model.defer="city" placeholder="Contoh: Semarang">
                    @error('city') <small class="error-text">{{ $message }}</small> @enderror
                </label>

                <label>
                    Provinsi
                    <input type="text" wire:model.defer="province" placeholder="Contoh: Jawa Tengah">
                    @error('province') <small class="error-text">{{ $message }}</small> @enderror
                </label>

                <label>
                    Kode Pos
                    <input type="text" wire:model.defer="postal_code" placeholder="Contoh: 502xx">
                    @error('postal_code') <small class="error-text">{{ $message }}</small> @enderror
                </label>
            </div>

            <label>
                Alamat Lengkap
                <textarea wire:model.defer="address" rows="3" placeholder="Alamat lengkap pengiriman"></textarea>
                @error('address') <small class="error-text">{{ $message }}</small> @enderror
            </label>

            <div class="customer-form-grid">
                <label>
                    Password
                    <input type="password" wire:model.defer="password" placeholder="Minimal 8 karakter">
                    @error('password') <small class="error-text">{{ $message }}</small> @enderror
                </label>

                <label>
                    Konfirmasi Password
                    <input type="password" wire:model.defer="password_confirmation" placeholder="Ulangi password">
                </label>
            </div>

            <button type="submit">Sign Up</button>

            <p class="customer-auth-switch">
                Sudah punya akun?
                <a href="{{ route('customer.login') }}" wire:navigate>Masuk</a>
            </p>
        </form>
    </div>
</section>