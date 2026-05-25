<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.customer-auth')]
#[Title('Sign In - Compify')]
class extends Component {
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::guard('customer')->attempt([
            'email' => $this->email,
            'password' => $this->password,
        ], $this->remember)) {
            $this->addError('email', 'Email atau password salah.');
            return;
        }

        request()->session()->regenerate();

        if (Auth::guard('customer')->user()->role === 'admin') {
            Auth::guard('customer')->logout();

            $this->addError('email', 'Akun admin tidak digunakan untuk login customer.');
            return;
        }

        $this->redirectRoute('home', navigate: true);
    }
};
?>

<section class="customer-auth-v2">
    <div class="customer-auth-card-v2">
        <div class="customer-auth-visual">
            <a href="{{ route('home') }}" class="customer-auth-back" wire:navigate>
                Back to website →
            </a>

            <div class="customer-auth-logo">Compify</div>

            <div class="customer-auth-visual-text">
                <h2>Build Your Setup</h2>
                <p>Masuk untuk menyimpan wishlist, keranjang, dan data pemesanan.</p>
            </div>

            <div class="customer-auth-dots">
                <span></span>
                <span></span>
                <span class="active"></span>
            </div>
        </div>

        <div class="customer-auth-panel">
            <h1>Sign in</h1>

            <p class="customer-auth-switch">
                Belum punya akun?
                <a href="{{ route('customer.register') }}" wire:navigate>Register here</a>
            </p>

            @if($errors->any())
                <div class="auth-alert">{{ $errors->first() }}</div>
            @endif

            <form wire:submit="login" class="customer-auth-form-v2">
                <label>
                    Email
                    <input type="email" wire:model.defer="email" placeholder="Enter your email address">
                    @error('email') <small class="error-text">{{ $message }}</small> @enderror
                </label>

                <label>
                    Password
                    <input type="password" wire:model.defer="password" placeholder="Enter your password">
                    @error('password') <small class="error-text">{{ $message }}</small> @enderror
                </label>

                <div class="customer-auth-row">
                    <label class="customer-auth-check-v2">
                        <input type="checkbox" wire:model="remember">
                        <span>Remember me</span>
                    </label>
                </div>

                <button type="submit" class="customer-auth-submit-v2">
                    Login
                </button>
            </form>

            <div class="customer-auth-divider">
                <span></span>
                <p>or continue with</p>
                <span></span>
            </div>

            <div class="customer-social-buttons">
                <button type="button" title="Google login belum dikonfigurasi">
                    <svg viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M21.6 12.2c0-.7-.1-1.4-.2-2H12v3.8h5.4c-.2 1.2-.9 2.2-1.9 2.9v2.4h3.1c1.8-1.7 3-4.1 3-7.1Z"/>
                        <path fill="#34A853" d="M12 22c2.7 0 5-0.9 6.6-2.5l-3.1-2.4c-.9.6-2 1-3.5 1-2.7 0-4.9-1.8-5.7-4.2H3.1v2.5C4.7 19.7 8.1 22 12 22Z"/>
                        <path fill="#FBBC05" d="M6.3 13.9c-.2-.6-.3-1.2-.3-1.9s.1-1.3.3-1.9V7.6H3.1C2.4 8.9 2 10.4 2 12s.4 3.1 1.1 4.4l3.2-2.5Z"/>
                        <path fill="#EA4335" d="M12 5.9c1.5 0 2.8.5 3.8 1.5l2.9-2.9C16.9 2.9 14.7 2 12 2 8.1 2 4.7 4.3 3.1 7.6l3.2 2.5C7.1 7.7 9.3 5.9 12 5.9Z"/>
                    </svg>
                    Google
                </button>

                <button type="button" title="Apple login belum dikonfigurasi">
                    <svg viewBox="0 0 24 24">
                        <path d="M16.4 13.1c0-2.2 1.8-3.3 1.9-3.4-1-1.5-2.6-1.7-3.1-1.7-1.3-.1-2.6.8-3.3.8-.7 0-1.7-.8-2.8-.8-1.5 0-2.9.9-3.7 2.2-1.6 2.8-.4 6.9 1.1 9.2.8 1.1 1.7 2.3 2.9 2.3 1.1 0 1.6-.7 3-.7s1.8.7 3 .7c1.2 0 2-1.1 2.8-2.2.9-1.3 1.2-2.5 1.2-2.6-.1 0-2.9-1.1-3-3.8ZM14.3 6.6c.6-.8 1-1.8.9-2.9-.9 0-1.9.6-2.6 1.3-.6.7-1.1 1.8-1 2.8 1 .1 2-.5 2.7-1.2Z"/>
                    </svg>
                    Apple
                </button>
            </div>

            <p class="customer-social-note">
                Tombol Google/Apple ini tampilan dulu. Login sosial asli nanti pakai Laravel Socialite.
            </p>
        </div>
    </div>
</section>